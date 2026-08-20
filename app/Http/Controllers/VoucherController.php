<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVoucherRequest;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountMappingService;
use App\Services\JournalEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cash/Bank Payment & Receipt vouchers — each is just a 2-line JournalEntry
 * with one leg fixed to the mapped Cash/Bank control account and the other
 * leg (the "other account") picked by the user. Payment vouchers debit the
 * other account and credit Cash/Bank (money out); Receipt vouchers debit
 * Cash/Bank and credit the other account (money in).
 */
class VoucherController extends Controller
{
    private const TYPES = [
        'cash-payment' => [
            'type' => JournalEntry::TYPE_CASH_PAYMENT, 'label' => 'Cash Payment Voucher',
            'fixed_leg' => 'cash', 'fixed_role' => 'credit', 'other_label' => 'Debit Account (expense/payable/etc.)',
        ],
        'cash-receipt' => [
            'type' => JournalEntry::TYPE_CASH_RECEIPT, 'label' => 'Cash Receipt Voucher',
            'fixed_leg' => 'cash', 'fixed_role' => 'debit', 'other_label' => 'Credit Account (revenue/receivable/etc.)',
        ],
        'bank-payment' => [
            'type' => JournalEntry::TYPE_BANK_PAYMENT, 'label' => 'Bank Payment Voucher',
            'fixed_leg' => 'bank', 'fixed_role' => 'credit', 'other_label' => 'Debit Account (expense/payable/etc.)',
        ],
        'bank-receipt' => [
            'type' => JournalEntry::TYPE_BANK_RECEIPT, 'label' => 'Bank Receipt Voucher',
            'fixed_leg' => 'bank', 'fixed_role' => 'debit', 'other_label' => 'Credit Account (revenue/receivable/etc.)',
        ],
    ];

    public function __construct(
        private readonly JournalEntryService $journal,
        private readonly AccountMappingService $mapping,
    ) {
    }

    public function create(string $type): View
    {
        $this->authorize('create', JournalEntry::class);

        $config = $this->configFor($type);
        $fixedAccount = $config['fixed_leg'] === 'cash' ? $this->mapping->cashAccount() : $this->mapping->bankAccount();

        return view('vouchers.create', [
            'type' => $type,
            'config' => $config,
            'fixedAccount' => $fixedAccount,
            'accounts' => Account::where('status', Account::STATUS_ACTIVE)->orderBy('code')->get(),
        ]);
    }

    public function store(StoreVoucherRequest $request, string $type): RedirectResponse
    {
        $this->authorize('create', JournalEntry::class);

        $config = $this->configFor($type);
        $fixedAccount = $config['fixed_leg'] === 'cash' ? $this->mapping->cashAccount() : $this->mapping->bankAccount();

        if (! $fixedAccount) {
            return back()->withInput()->with('error', 'No '.($config['fixed_leg'] === 'cash' ? 'Cash' : 'Bank')." account is mapped yet — set it up in Account Mapping first.");
        }

        $validated = $request->validated();
        $otherAccount = Account::findOrFail($validated['account_id']);

        [$debitAccount, $creditAccount] = $config['fixed_role'] === 'debit'
            ? [$fixedAccount, $otherAccount]
            : [$otherAccount, $fixedAccount];

        $entry = $this->journal->postSimple(
            type: $config['type'],
            entryDate: $validated['entry_date'],
            debitAccount: $debitAccount,
            creditAccount: $creditAccount,
            amount: (float) $validated['amount'],
            narration: $validated['narration'],
        );

        return redirect()->route('journal-entries.show', $entry)->with('status', $config['label'].' recorded successfully.');
    }

    /**
     * @return array{type: string, label: string, fixed_leg: string, fixed_role: string, other_label: string}
     */
    private function configFor(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404, "Unknown voucher type: {$type}");

        return self::TYPES[$type];
    }
}
