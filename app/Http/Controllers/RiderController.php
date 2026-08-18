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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RiderController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
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
    public function manifest(RiderProfile $rider): Response
    {
        $this->authorize('dispatch.view');

        $orders = $this->activeOrdersFor($rider);

        return Pdf::loadView('orders.report-pdf', compact('orders'))
            ->setPaper('a4', 'landscape')
            ->download("delivery-manifest-{$rider->user->name}-".now()->format('Y-m-d').'.pdf');
    }

    public function manifestExcel(RiderProfile $rider): BinaryFileResponse
    {
        $this->authorize('dispatch.view');

        $orders = $this->activeOrdersFor($rider);

        return Excel::download(new OrdersExport($orders), "delivery-manifest-{$rider->user->name}-".now()->format('Y-m-d').'.xlsx');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Order>
     */
    private function activeOrdersFor(RiderProfile $rider): \Illuminate\Database\Eloquent\Collection
    {
        $rider->load('user');

        return Order::with(['items', 'rider.user'])
            ->where('rider_id', $rider->id)
            ->whereIn('delivery_status', [
                Order::DELIVERY_STATUS_ASSIGNED,
                Order::DELIVERY_STATUS_PICKED_UP,
                Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            ])
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

        $this->auditLog->log('checked_in', 'riders', $rider, null, ['is_checked_in' => true]);

        return back()->with('status', "{$rider->user->name} checked in — now assignable on the Dispatch Board.");
    }

    public function checkOut(RiderProfile $rider): RedirectResponse
    {
        $this->authorize('update', $rider);

        $rider->update(['is_checked_in' => false]);

        $this->auditLog->log('checked_out', 'riders', $rider, null, ['is_checked_in' => false]);

        return back()->with('status', "{$rider->user->name} checked out.");
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
