<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const COMPANY_KEYS = ['company_name', 'company_email', 'company_phone', 'company_address'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function edit(): View
    {
        $this->authorize('settings.view');

        return view('settings.edit', [
            'settings' => $this->settings->group('company'),
            'inventorySettings' => $this->settings->group('inventory'),
            'warehouses' => Warehouse::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('settings.edit');

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_address' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (self::COMPANY_KEYS as $key) {
            $this->settings->set($key, $validated[$key] ?? null, 'company');
        }

        $this->auditLog->log('updated', 'settings', null, null, $validated);

        return back()->with('status', 'Settings updated successfully.');
    }

    public function updateInventory(Request $request): RedirectResponse
    {
        $this->authorize('settings.edit');

        $validated = $request->validate([
            'default_warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $this->settings->set('default_warehouse_id', $validated['default_warehouse_id'], 'inventory');

        $this->auditLog->log('updated', 'settings', null, null, $validated);

        return back()->with('status', 'Inventory settings updated successfully.');
    }
}
