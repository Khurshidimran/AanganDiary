<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::with('parent')->orderBy('code')->get();

        return view('accounts.index', compact('accounts'));
    }

    public function create(): View
    {
        $this->authorize('create', Account::class);

        return view('accounts.create', [
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = Account::create($request->validated());

        $this->auditLog->log('created', 'accounts', $account, null, $account->only(['code', 'name', 'type']));

        return redirect()->route('accounts.index')->with('status', 'Account created successfully.');
    }

    public function edit(Account $account): View
    {
        $this->authorize('update', $account);

        return view('accounts.edit', [
            'account' => $account,
            'parents' => $this->parentOptions($account),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $before = $account->only(['code', 'name', 'type', 'status']);

        $account->update($request->validated());

        $this->auditLog->log('updated', 'accounts', $account, $before, $account->only(['code', 'name', 'type', 'status']));

        return redirect()->route('accounts.index')->with('status', 'Account updated successfully.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        if ($account->is_system) {
            return back()->with('error', "Cannot delete \"{$account->name}\" — it's a system-protected control account.");
        }

        if ($account->lines()->exists() || $account->children()->exists()) {
            return back()->with('error', "Cannot delete \"{$account->name}\" — it has journal activity or sub-accounts.");
        }

        $before = $account->only(['code', 'name', 'type']);
        $account->delete();

        $this->auditLog->log('deleted', 'accounts', null, $before, null);

        return redirect()->route('accounts.index')->with('status', 'Account deleted successfully.');
    }

    /**
     * @return array<string, string>
     */
    private function parentOptions(?Account $excluding = null): array
    {
        return Account::when($excluding, fn ($q) => $q->where('id', '!=', $excluding->id))
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (Account $a) => [$a->id => "{$a->code} — {$a->name}"])
            ->all();
    }
}
