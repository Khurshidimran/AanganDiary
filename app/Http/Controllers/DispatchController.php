<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RiderProfile;
use App\Services\AuditLogService;
use App\Services\DispatchService;
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

    public function index(): View
    {
        $this->authorize('dispatch.view');

        $awaiting = Order::where('order_status', Order::ORDER_STATUS_CONFIRMED)
            ->whereNull('rider_id')
            ->latest('shopify_created_at')
            ->get();

        $inProgress = Order::whereIn('delivery_status', [
            Order::DELIVERY_STATUS_ASSIGNED,
            Order::DELIVERY_STATUS_PICKED_UP,
            Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        ])->with('rider.user')->orderBy('assigned_at')->get();

        // Only riders who've checked in (location-verified at their warehouse
        // via the mobile app) are eligible for assignment — see
        // Api\Rider\RiderStatusController::checkIn().
        $riders = RiderProfile::with('user')
            ->where('status', RiderProfile::STATUS_ACTIVE)
            ->where('is_checked_in', true)
            ->get();

        return view('dispatch.index', compact('awaiting', 'inProgress', 'riders'));
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

        if (! $order->canBeMarkedOutForDelivery()) {
            return back()->with('error', 'This order has not been picked up yet.');
        }

        $this->dispatch->markOutForDelivery($order);
        $this->auditLog->log('out_for_delivery', 'orders', $order, null, ['delivery_status' => $order->delivery_status]);

        return back()->with('status', 'Order marked as out for delivery.');
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
