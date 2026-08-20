<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\DispatchService;
use App\Services\SettingsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DispatchController extends Controller
{
    public function __construct(
        private readonly DispatchService $dispatch,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('dispatch.view');

        // Defaults to today, matching the board's original "live ops view"
        // behavior — widen the range (or clear it) to review other days.
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : today()->startOfDay();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : today()->endOfDay();

        $orders = Order::with(['items', 'rider.user', 'rider.warehouse'])
            ->whereBetween('shopify_created_at', [$dateFrom, $dateTo])
            ->where(function ($query) {
                $query
                    // "Pending" only means something a dispatcher can act on
                    // if it's actually confirmed — an order Shopify sent over
                    // that staff hasn't reviewed yet doesn't belong here (and
                    // canBeAssigned() would refuse it anyway, leaving the
                    // card with no controls at all).
                    ->where(fn ($q) => $q->where('order_status', Order::ORDER_STATUS_CONFIRMED)
                        ->where('delivery_status', Order::DELIVERY_STATUS_PENDING))
                    ->orWhereIn('delivery_status', [
                        Order::DELIVERY_STATUS_FAILED,
                        Order::DELIVERY_STATUS_ASSIGNED,
                        Order::DELIVERY_STATUS_PICKED_UP,
                        Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
                        Order::DELIVERY_STATUS_DELIVERED,
                        Order::DELIVERY_STATUS_RETURNED,
                    ]);
            })
            // A cancelled order that never got past "pending" is just noise
            // here, but one already out with a rider (assigned/picked_up/
            // out_for_delivery) still needs to show — staff must know to
            // pull it back, mirroring the isCancelled() guards on the
            // per-order action buttons below.
            ->where(function ($query) {
                $query->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
                    ->orWhereIn('delivery_status', [
                        Order::DELIVERY_STATUS_ASSIGNED,
                        Order::DELIVERY_STATUS_PICKED_UP,
                        Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
                    ]);
            })
            ->orderByRaw("FIELD(delivery_status, 'pending','failed','assigned','picked_up','out_for_delivery','delivered','returned')")
            ->orderBy('shopify_created_at')
            ->get();

        // Only riders who've checked in (location-verified at their warehouse
        // via the mobile app) are eligible for assignment — see
        // Api\Rider\RiderStatusController::checkIn().
        $riders = RiderProfile::with(['user', 'warehouse'])
            ->where('status', RiderProfile::STATUS_ACTIVE)
            ->where('is_checked_in', true)
            ->get()
            ->sortBy(fn (RiderProfile $r) => $r->user->name)
            ->values();

        // Derived from the already date-filtered $orders above (rather than
        // a separate query) so "Rider Workload" can never drift out of sync
        // with the date range applied to the main board — this was the bug:
        // the old separate eager-load ignored date_from/date_to entirely.
        $ordersByRider = $orders->whereIn('delivery_status', [
            Order::DELIVERY_STATUS_ASSIGNED,
            Order::DELIVERY_STATUS_PICKED_UP,
            Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        ])->groupBy('rider_id');

        $riders->each(fn (RiderProfile $rider) => $rider->setRelation('orders', $ordersByRider->get($rider->id, collect())));

        $statusCounts = [
            'pending' => $orders->where('delivery_status', Order::DELIVERY_STATUS_PENDING)->count(),
            'failed' => $orders->where('delivery_status', Order::DELIVERY_STATUS_FAILED)->count(),
            'assigned' => $orders->where('delivery_status', Order::DELIVERY_STATUS_ASSIGNED)->count(),
            'picked_up' => $orders->where('delivery_status', Order::DELIVERY_STATUS_PICKED_UP)->count(),
            'out_for_delivery' => $orders->where('delivery_status', Order::DELIVERY_STATUS_OUT_FOR_DELIVERY)->count(),
            'delivered' => $orders->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count(),
            'returned' => $orders->where('delivery_status', Order::DELIVERY_STATUS_RETURNED)->count(),
        ];

        $defaultWarehouseId = app(SettingsService::class)->group('inventory')->get('default_warehouse_id');
        $defaultWarehouse = $defaultWarehouseId ? Warehouse::find($defaultWarehouseId) : null;

        return view('dispatch.index', compact('orders', 'riders', 'statusCounts', 'defaultWarehouse', 'dateFrom', 'dateTo'));
    }

    public function unassign(Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if (! $order->canBeUnassigned()) {
            return back()->with('error', 'This order cannot be unassigned right now.');
        }

        $riderName = $order->rider?->user->name;

        $this->dispatch->unassign($order);
        $this->auditLog->log('unassigned', 'orders', $order, ['rider' => $riderName], null);

        return back()->with('status', 'Rider unassigned — order is pending again.');
    }

    public function assign(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if (! $order->canBeAssigned()) {
            return back()->with('error', 'This order cannot be assigned right now.');
        }

        $validated = $request->validate([
            'rider_id' => ['required', 'exists:rider_profiles,id'],
            'rider_instructions' => ['nullable', 'string', 'max:1000'],
            'scheduled_dispatch_at' => ['nullable', 'date'],
        ]);
        $rider = RiderProfile::findOrFail($validated['rider_id']);

        $this->dispatch->assign(
            $order,
            $rider,
            $validated['rider_instructions'] ?? null,
            isset($validated['scheduled_dispatch_at']) ? Carbon::parse($validated['scheduled_dispatch_at']) : null,
        );
        $this->auditLog->log('assigned', 'orders', $order, null, ['rider_id' => $rider->id]);

        return back()->with('status', "Order assigned to {$rider->user->name}.");
    }

    public function pickedUp(Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if ($order->isCancelled()) {
            return back()->with('error', 'This order was cancelled in Shopify and cannot be picked up.');
        }

        if (! $order->canBeMarkedPickedUp()) {
            return back()->with('error', 'This order is not in an assigned state.');
        }

        $this->dispatch->markPickedUp($order);
        $this->auditLog->log('picked_up', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);

        return back()->with('status', 'Order marked as picked up.');
    }

    public function outForDelivery(Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if ($order->isCancelled()) {
            return back()->with('error', 'This order was cancelled in Shopify and cannot be marked out for delivery.');
        }

        if (! $order->canBeMarkedOutForDelivery()) {
            return back()->with('error', 'This order has not been picked up yet.');
        }

        $this->dispatch->markOutForDelivery($order);
        $this->auditLog->log('out_for_delivery', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);

        return back()->with('status', 'Order marked as out for delivery.');
    }

    public function bulkAssign(Request $request): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
            'rider_id' => ['required', 'exists:rider_profiles,id'],
        ]);

        $rider = RiderProfile::findOrFail($validated['rider_id']);
        $orders = Order::whereIn('id', $validated['order_ids'])->get();
        $result = $this->dispatch->markManyAssigned($orders, $rider);

        foreach ($result['succeeded'] as $order) {
            $this->auditLog->log('assigned', 'orders', $order, null, ['rider_id' => $rider->id]);
        }

        return back()->with($this->bulkFlash($result, "assigned to {$rider->user->name}"));
    }

    public function bulkPickedUp(Request $request): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])->get();
        $result = $this->dispatch->markManyPickedUp($orders);

        foreach ($result['succeeded'] as $order) {
            $this->auditLog->log('picked_up', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);
        }

        return back()->with($this->bulkFlash($result, 'picked up'));
    }

    public function bulkOutForDelivery(Request $request): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
        ]);

        $orders = Order::whereIn('id', $validated['order_ids'])->get();
        $result = $this->dispatch->markManyOutForDelivery($orders);

        foreach ($result['succeeded'] as $order) {
            $this->auditLog->log('out_for_delivery', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);
        }

        return back()->with($this->bulkFlash($result, 'out for delivery'));
    }

    public function delivered(Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if ($order->isCancelled()) {
            return back()->with('error', 'This order was cancelled in Shopify and cannot be marked delivered.');
        }

        if (! $order->canBeMarkedDelivered()) {
            return back()->with('error', 'This order is not out for delivery.');
        }

        $this->dispatch->markDelivered($order);
        $this->auditLog->log('delivered', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);

        return back()->with('status', 'Order marked as delivered.');
    }

    public function failed(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if (! $order->canBeMarkedFailedOrReturned()) {
            return back()->with('error', 'This order cannot be marked as failed right now.');
        }

        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $this->dispatch->markFailed($order, $validated['reason']);
        $this->auditLog->log('delivery_failed', 'orders', $order, null, ['reason' => $validated['reason']]);

        return back()->with('status', 'Order marked as delivery failed.');
    }

    public function returned(Order $order): RedirectResponse
    {
        $this->authorize('dispatch.manage');

        if (! $order->canBeMarkedFailedOrReturned()) {
            return back()->with('error', 'This order cannot be marked as returned right now.');
        }

        $this->dispatch->markReturned($order);

        $this->auditLog->log('returned', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);

        return back()->with('status', 'Order marked as returned; stock released back.');
    }

    /**
     * @param  array{succeeded: list<Order>, skipped: list<array{order: Order, reason: string}>}  $result
     * @return array<string, string>
     */
    private function bulkFlash(array $result, string $verb): array
    {
        $succeededCount = count($result['succeeded']);
        $skippedCount = count($result['skipped']);

        if ($succeededCount === 0) {
            return ['error' => "No orders were marked {$verb} — none of the selected orders are eligible right now."];
        }

        $message = "{$succeededCount} order".($succeededCount === 1 ? '' : 's')." marked {$verb}.";

        if ($skippedCount > 0) {
            $reasons = collect($result['skipped'])
                ->map(fn (array $entry) => ($entry['order']->shopify_order_number ?? $entry['order']->shopify_order_id).' ('.$entry['reason'].')')
                ->take(3)
                ->implode(', ');

            $message .= " {$skippedCount} skipped — not eligible: {$reasons}".($skippedCount > 3 ? ', ...' : '').'.';
        }

        return ['status' => $message];
    }
}
