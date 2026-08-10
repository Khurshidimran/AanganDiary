<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Unit::class);

        $units = Unit::orderBy('name')->paginate(20);

        return view('units.index', compact('units'));
    }

    public function create(): View
    {
        $this->authorize('create', Unit::class);

        return view('units.create');
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        $this->auditLog->log('created', 'units', $unit, null, $unit->only(['name', 'short_code', 'status']));

        return redirect()->route('units.index')->with('status', 'Unit created successfully.');
    }

    public function edit(Unit $unit): View
    {
        $this->authorize('update', $unit);

        return view('units.edit', compact('unit'));
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $before = $unit->only(['name', 'short_code', 'status']);

        $unit->update($request->validated());

        $this->auditLog->log('updated', 'units', $unit, $before, $unit->only(['name', 'short_code', 'status']));

        return redirect()->route('units.index')->with('status', 'Unit updated successfully.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        if ($unit->products()->exists() || $unit->variants()->exists()) {
            return redirect()->route('units.index')
                ->with('error', "Cannot delete \"{$unit->name}\" — it is still used by one or more products or variants.");
        }

        $before = $unit->only(['name', 'short_code', 'status']);
        $unit->delete();

        $this->auditLog->log('deleted', 'units', null, $before, null);

        return redirect()->route('units.index')->with('status', 'Unit deleted successfully.');
    }
}
