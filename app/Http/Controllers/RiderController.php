<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Http\Requests\StoreRiderRequest;
use App\Http\Requests\UpdateRiderRequest;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\RiderTripService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RiderController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly RiderTripService $trips,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', RiderProfile::class);

        $riders = RiderProfile::with('user', 'warehouse')->orderBy('created_at', 'desc')->paginate(20);

        return view('riders.index', compact('riders'));
    }

    /**
     * A shareable/printable list of everything currently in this rider's
     * hands — the same active-delivery set shown on the Dispatch Board,
     * just scoped to one rider instead of shown flat across all of them.
     * Reuses the exact same report view/columns as the main Orders export
     * (orders.report-pdf / OrdersExport) so the two are always identical in
     * shape — a rider-scoped slice of the same report, not a separate one.
     */
    public function manifest(Request $request, RiderProfile $rider): Response
    {
        $this->authorize('dispatch.view');

        $orders = $this->activeOrdersFor($rider, $request->query('date_from'), $request->query('date_to'));

        return Pdf::loadView('orders.report-pdf', compact('orders'))
            ->setPaper('a4', 'landscape')
            ->download("delivery-manifest-{$rider->user->name}-".now()->format('Y-m-d').'.pdf');
    }

    public function manifestExcel(Request $request, RiderProfile $rider): BinaryFileResponse
    {
        $this->authorize('dispatch.view');

        $orders = $this->activeOrdersFor($rider, $request->query('date_from'), $request->query('date_to'));

        return Excel::download(new OrdersExport($orders), "delivery-manifest-{$rider->user->name}-".now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Orders vs Riders — for a date range, how many orders each rider was
     * assigned, broken down by current status, plus the total order value.
     * Lets a manager judge workload/performance distribution across riders.
     */
    public function report(Request $request): View
    {
        $this->authorize('dispatch.view');

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : now()->endOfDay();

        $orders = Order::with('rider.user')
            ->whereNotNull('rider_id')
            // A cancelled order shouldn't count toward a rider's workload —
            // it was never actually delivered regardless of how far dispatch
            // got before Shopify cancelled it.
            ->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
            ->whereBetween('shopify_created_at', [$dateFrom, $dateTo])
            ->get();

        // Each status bucket carries both a count and that bucket's own order
        // value (not just the row's overall total) — e.g. "19 / 2,000" for
        // Delivered means those 19 delivered orders total Rs. 2,000, distinct
        // from whatever's still failed/returned/in-flight.
        $bucket = fn ($riderOrders, string $status) => [
            'count' => $riderOrders->where('delivery_status', $status)->count(),
            'amount' => $riderOrders->where('delivery_status', $status)->sum('total'),
        ];

        $rows = $orders->groupBy('rider_id')
            ->map(fn ($riderOrders) => [
                'rider' => $riderOrders->first()->rider,
                'total_orders' => $riderOrders->count(),
                'assigned' => $bucket($riderOrders, Order::DELIVERY_STATUS_ASSIGNED),
                'picked_up' => $bucket($riderOrders, Order::DELIVERY_STATUS_PICKED_UP),
                'out_for_delivery' => $bucket($riderOrders, Order::DELIVERY_STATUS_OUT_FOR_DELIVERY),
                'delivered' => $bucket($riderOrders, Order::DELIVERY_STATUS_DELIVERED),
                'failed' => $bucket($riderOrders, Order::DELIVERY_STATUS_FAILED),
                'returned' => $bucket($riderOrders, Order::DELIVERY_STATUS_RETURNED),
                'total_amount' => $riderOrders->sum('total'),
            ])
            ->sortByDesc('total_orders')
            ->values();

        return view('riders.report', [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'grandTotalOrders' => $rows->sum('total_orders'),
            'grandTotalAmount' => $rows->sum('total_amount'),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    private function activeOrdersFor(RiderProfile $rider, ?string $dateFrom = null, ?string $dateTo = null): \Illuminate\Database\Eloquent\Collection
    {
        $rider->load('user');

        return Order::with(['items', 'rider.user'])
            ->where('rider_id', $rider->id)
            ->whereIn('delivery_status', [
                Order::DELIVERY_STATUS_ASSIGNED,
                Order::DELIVERY_STATUS_PICKED_UP,
                Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            ])
            // Matches whatever date range is currently applied on the
            // Dispatch Board — without this, the manifest silently included
            // every active order for the rider regardless of the filter
            // staff had selected there.
            ->when($dateFrom, fn ($q) => $q->where('shopify_created_at', '>=', Carbon::parse($dateFrom)->startOfDay()))
            ->when($dateTo, fn ($q) => $q->where('shopify_created_at', '<=', Carbon::parse($dateTo)->endOfDay()))
            ->orderBy('assigned_at')
            ->get();
    }

    /**
     * Admin-side manual override for check-in — no location check, since a
     * staff member vouching for the rider is trusted the same way the
     * rider's own location-verified mobile check-in is (see
     * Api\Rider\RiderStatusController::checkIn()). Needed for riders whose
     * app can't check in yet (not updated, phone issue, etc.) and for any
     * rider that predates the check-in feature — without this, such a
     * rider simply never appears as assignable on the Dispatch Board.
     */
    public function checkIn(RiderProfile $rider): RedirectResponse
    {
        $this->authorize('update', $rider);

        $rider->update(['is_checked_in' => true, 'checked_in_at' => now()]);
        $this->trips->openTrip($rider);

        $this->auditLog->log('checked_in', 'riders', $rider, null, ['is_checked_in' => true]);

        return back()->with('status', "{$rider->user->name} checked in — now assignable on the Dispatch Board.");
    }

    public function checkOut(RiderProfile $rider): RedirectResponse
    {
        $this->authorize('update', $rider);

        $rider->update(['is_checked_in' => false]);
        $this->trips->closeTrip($rider);

        $this->auditLog->log('checked_out', 'riders', $rider, null, ['is_checked_in' => false]);

        return back()->with('status', "{$rider->user->name} checked out.");
    }

    public function deactivate(RiderProfile $rider): RedirectResponse
    {
        $this->authorize('update', $rider);

        $rider->update(['status' => RiderProfile::STATUS_INACTIVE]);

        $this->auditLog->log('deactivated', 'riders', $rider, null, ['status' => RiderProfile::STATUS_INACTIVE]);

        return back()->with('status', "{$rider->user->name} deactivated.");
    }

    public function create(): View
    {
        $this->authorize('create', RiderProfile::class);

        return view('riders.create', [
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreRiderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $rider = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone' => $validated['phone'],
                'status' => $validated['status'] === 'active' ? 'active' : 'inactive',
            ]);

            $user->syncRoles(['Rider']);

            return $user->riderProfile()->create([
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'phone' => $validated['phone'],
                'cnic' => $validated['cnic'] ?? null,
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'zone' => $validated['zone'] ?? null,
                'per_delivery_rate' => $validated['per_delivery_rate'],
                'status' => $validated['status'],
            ]);
        });

        $this->auditLog->log('created', 'riders', $rider, null, ['phone' => $rider->phone, 'status' => $rider->status]);

        return redirect()->route('riders.index')->with('status', 'Rider created successfully.');
    }

    public function edit(RiderProfile $rider): View
    {
        $this->authorize('update', $rider);

        $rider->load('user', 'warehouse');

        return view('riders.edit', [
            'rider' => $rider,
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateRiderRequest $request, RiderProfile $rider): RedirectResponse
    {
        $validated = $request->validated();
        $before = $rider->only(['phone', 'status']);

        DB::transaction(function () use ($rider, $validated) {
            $rider->user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'] === 'active' ? 'active' : 'inactive',
            ]);

            if (! empty($validated['password'])) {
                $rider->user->password = $validated['password'];
            }

            $rider->user->save();

            $rider->update([
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'phone' => $validated['phone'],
                'cnic' => $validated['cnic'] ?? null,
                'vehicle_type' => $validated['vehicle_type'],
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'zone' => $validated['zone'] ?? null,
                'per_delivery_rate' => $validated['per_delivery_rate'],
                'status' => $validated['status'],
            ]);
        });

        $this->auditLog->log('updated', 'riders', $rider, $before, $rider->only(['phone', 'status']));

        return redirect()->route('riders.index')->with('status', 'Rider updated successfully.');
    }

    public function destroy(RiderProfile $rider): RedirectResponse
    {
        $this->authorize('delete', $rider);

        if ((float) $rider->wallet_balance !== 0.0) {
            return back()->with('error', 'Cannot delete a rider with an unsettled wallet balance. Settle it first.');
        }

        if (Order::where('rider_id', $rider->id)->whereNotIn('delivery_status', ['delivered', 'failed', 'returned'])->exists()) {
            return back()->with('error', 'Cannot delete a rider with active deliveries assigned.');
        }

        $before = $rider->only(['phone', 'status']);

        DB::transaction(function () use ($rider) {
            $rider->delete();
            $rider->user->delete();
        });

        $this->auditLog->log('deleted', 'riders', null, $before, null);

        return redirect()->route('riders.index')->with('status', 'Rider deleted successfully.');
    }
}
