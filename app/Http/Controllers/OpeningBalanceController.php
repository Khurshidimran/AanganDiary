<?php

namespace App\Http\Controllers;

use App\Exceptions\UnbalancedJournalEntryException;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One-time (or re-done-after-voiding) setup step for a business starting
 * this accounting system mid-year — lets staff say "this account already
 * had this much as of this date" per balance-sheet account, without needing
 * to think in debits/credits themselves. Revenue/Expense accounts aren't
 * included: they don't carry a balance forward between periods the way
 * Asset/Liability/Equity accounts do.
 */
class OpeningBalanceController extends Controller
{
    private const OPENING_BALANCE_EQUITY_CODE = '3150';

    public function __construct(private readonly JournalEntryService $journal)
    {
    }

    public function edit(): View
    {
        $this->authorize('accounting.manage');

        $existing = JournalEntry::where('type', JournalEntry::TYPE_OPENING_BALANCE)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->with('lines.account')
            ->first();

        $plugAccount = Account::where('code', self::OPENING_BALANCE_EQUITY_CODE)->first();

        $accounts = Account::whereIn('type', [Account::TYPE_ASSET, Account::TYPE_LIABILITY, Account::TYPE_EQUITY])
            ->where('status', Account::STATUS_ACTIVE)
            ->when($plugAccount, fn ($q) => $q->where('id', '!=', $plugAccount->id))
            ->orderBy('code')
            ->get();

        return view('accounting.opening-balances', [
            'accounts' => $accounts,
            'existing' => $existing,
            'plugAccount' => $plugAccount,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('accounting.manage');

        if (JournalEntry::where('type', JournalEntry::TYPE_OPENING_BALANCE)->where('status', JournalEntry::STATUS_POSTED)->exists()) {
            return back()->with('error', 'An opening balance has already been recorded. Void it first (from Journal Entries) before entering a new one.');
        }

        $plugAccount = Account::where('code', self::OPENING_BALANCE_EQUITY_CODE)->first();

        if (! $plugAccount) {
            return back()->with('error', 'Chart of Accounts is not fully set up — the Opening Balance Equity account is missing.');
        }

        // A blank number input submits as an empty string, not an absent
        // field — normalize to null first so 'nullable' actually applies
        // instead of 'numeric' rejecting every untouched account.
        $request->merge([
            'amounts' => collect($request->input('amounts', []))
                ->map(fn ($value) => $value === '' ? null : $value)
                ->all(),
        ]);

        $validated = $request->validate([
            'as_of_date' => ['required', 'date'],
            'amounts' => ['required', 'array'],
            'amounts.*' => ['nullable', 'numeric'],
        ]);

        $asOf = Carbon::parse($validated['as_of_date']);

        $accounts = Account::whereIn('id', array_keys(array_filter(
            $validated['amounts'],
            fn ($amount) => $amount !== null && (float) $amount != 0.0,
        )))->get()->keyBy('id');

        if ($accounts->isEmpty()) {
            return back()->withInput()->with('error', 'Enter a balance for at least one account.');
        }

        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $accountId => $account) {
            $amount = (float) $validated['amounts'][$accountId];
            $isDebitNormal = $account->normalBalance() === 'debit';
            // A positive amount sits on the account's own normal side; a
            // negative one (an account atypically running the other way —
            // an overdrawn bank account, say) flips it.
            $onNormalSide = $amount >= 0;
            $magnitude = abs($amount);

            $debitsThisLine = $onNormalSide === $isDebitNormal;

            $lines[] = [
                'account_id' => $accountId,
                'debit' => $debitsThisLine ? $magnitude : 0,
                'credit' => $debitsThisLine ? 0 : $magnitude,
            ];

            $totalDebit += $debitsThisLine ? $magnitude : 0;
            $totalCredit += $debitsThisLine ? 0 : $magnitude;
        }

        // Plug whatever's needed onto Opening Balance Equity so the entry
        // always balances — the user only ever thinks in per-account
        // balances, never in "does this add up."
        $difference = round($totalDebit - $totalCredit, 2);

        if ($difference != 0) {
            $lines[] = [
                'account_id' => $plugAccount->id,
                'debit' => $difference < 0 ? abs($difference) : 0,
                'credit' => $difference > 0 ? $difference : 0,
            ];
        }

        try {
            $entry = $this->journal->post(
                lines: $lines,
                type: JournalEntry::TYPE_OPENING_BALANCE,
                entryDate: $asOf,
                narration: 'Opening Balance as of '.$asOf->format('Y-m-d'),
            );
        } catch (UnbalancedJournalEntryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('journal-entries.show', $entry)->with('status', 'Opening balance recorded successfully.');
    }
}
