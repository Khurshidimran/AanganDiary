<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = Warehouse::orderBy('name')->paginate(20);

        return view('warehouses.index', compact('warehouses'));
    }

    public function create(): View
    {
        $this->authorize('create', Warehouse::class);

        return view('warehouses.create');
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::create($request->validated());

        $this->auditLog->log('created', 'warehouses', $warehouse, null, $warehouse->only(['name', 'code', 'status']));

        return redirect()->route('warehouses.index')->with('status', 'Warehouse created successfully.');
    }

    public function edit(Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $before = $warehouse->only(['name', 'code', 'status']);

        $warehouse->update($request->validated());

        $this->auditLog->log('updated', 'warehouses', $warehouse, $before, $warehouse->only(['name', 'code', 'status']));

        return redirect()->route('warehouses.index')->with('status', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $before = $warehouse->only(['name', 'code', 'status']);
        $warehouse->delete();

        $this->auditLog->log('deleted', 'warehouses', null, $before, null);

        return redirect()->route('warehouses.index')->with('status', 'Warehouse deleted successfully.');
    }
}
