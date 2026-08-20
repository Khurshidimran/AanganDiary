<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountMappingService;
use App\Services\AuditLogService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountMappingController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function edit(): View
    {
        $this->authorize('accounting.manage');

        return view('accounting.mapping', [
            'mapping' => $this->settings->group('accounting'),
            'accounts' => Account::where('status', Account::STATUS_ACTIVE)->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        $validated = $request->validate(
            collect(AccountMappingService::KEYS)->mapWithKeys(fn ($key) => [$key => ['nullable', 'exists:accounts,id']])->all(),
        );

        foreach (AccountMappingService::KEYS as $key) {
            $this->settings->set($key, $validated[$key] ?? null, 'accounting');
        }

        $this->auditLog->log('updated', 'accounting_mapping', null, null, $validated);

        return back()->with('status', 'Account mapping updated successfully.');
    }
}
