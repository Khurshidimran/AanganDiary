<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Vendor;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Vendor::class);

        $vendors = Vendor::orderBy('name')->paginate(20);

        return view('vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        $this->authorize('create', Vendor::class);

        return view('vendors.create');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $vendor = Vendor::create($request->validated());

        $this->auditLog->log('created', 'vendors', $vendor, null, $vendor->only(['name', 'status']));

        return redirect()->route('vendors.index')->with('status', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor): View
    {
        $this->authorize('view', $vendor);

        $vendor->load([
            'purchaseOrders' => fn ($q) => $q->latest('order_date'),
            'payments' => fn ($q) => $q->latest('payment_date'),
        ]);

        return view('vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorize('update', $vendor);

        return view('vendors.edit', compact('vendor'));
    }

    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $before = $vendor->only(['name', 'status']);

        $vendor->update($request->validated());

        $this->auditLog->log('updated', 'vendors', $vendor, $before, $vendor->only(['name', 'status']));

        return redirect()->route('vendors.index')->with('status', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorize('delete', $vendor);

        if ($vendor->purchaseOrders()->exists()) {
            return redirect()->route('vendors.index')
                ->with('error', "Cannot delete \"{$vendor->name}\" — it has purchase order history.");
        }

        $before = $vendor->only(['name', 'status']);
        $vendor->delete();

        $this->auditLog->log('deleted', 'vendors', null, $before, null);

        return redirect()->route('vendors.index')->with('status', 'Vendor deleted successfully.');
    }
}
