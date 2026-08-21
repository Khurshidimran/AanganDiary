<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PurchaseReceipt;
use App\Models\VendorPayment;

/**
 * Business-specific glue between the money-moving modules (Sales, Purchase,
 * Expense, Payroll) and the generic JournalEntryService — every method here
 * returns null (and posts nothing) when the relevant Account Mapping isn't
 * configured yet, or when there's genuinely nothing to book (e.g. a
 * zero-total order). Callers treat null as "skipped" and flash a warning;
 * the underlying business action (confirming an order, paying a vendor,
 * etc.) must never fail just because accounting isn't set up yet.
 */
class AccountingPostingService
{
    public function __construct(
        private readonly JournalEntryService $journal,
        private readonly AccountMappingService $mapping,
        private readonly OrderFulfillmentService $fulfillment,
    ) {
    }

    public function postSalesEntry(Order $order): ?JournalEntry
    {
        if ((float) $order->total <= 0 || $this->journal->hasPostedEntryFor('orders', $order->id)) {
            return null;
        }

        $revenueAccount = $this->mapping->salesRevenueAccount();
        $receivableAccount = $this->mapping->receivableAccount();
        $cashAccount = $this->mapping->cashAccount();

        if (! $revenueAccount || ! $receivableAccount || ! $cashAccount) {
            return null;
        }

        // A customer with their own linked ledger account (an optional,
        // per-customer setting — see Customer::account()) debits that
        // account instead of the shared pooled Receivable account; everyone
        // else nets through the shared one, same as before.
        $debitAccount = $order->payment_status === Order::PAYMENT_STATUS_PAID
            ? $cashAccount
            : ($order->customer?->account ?? $receivableAccount);

        $taxTotal = (float) $order->tax_total;
        $revenueAmount = (float) $order->total - $taxTotal;
        $taxAccount = $this->mapping->taxPayableAccount();

        $lines = [
            ['account_id' => $debitAccount->id, 'debit' => (float) $order->total, 'credit' => 0],
            ['account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => $taxAccount ? $revenueAmount : (float) $order->total],
        ];

        if ($taxTotal > 0 && $taxAccount) {
            $lines[] = ['account_id' => $taxAccount->id, 'debit' => 0, 'credit' => $taxTotal];
        }

        return $this->journal->post(
            lines: $lines,
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: now()->toDateString(),
            narration: "Sales — Order #{$order->shopify_order_number}",
            referenceType: 'orders',
            referenceId: $order->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    public function voidSalesEntry(Order $order, string $reason): void
    {
        $this->journal->voidEntryFor('orders', $order->id, $reason);
    }

    public function postCogsEntry(Order $order): ?JournalEntry
    {
        if ($this->journal->hasPostedEntryFor('order_cogs', $order->id)) {
            return null;
        }

        $cogsAccount = $this->mapping->cogsAccount();
        $inventoryAccount = $this->mapping->inventoryAssetAccount();

        if (! $cogsAccount || ! $inventoryAccount) {
            return null;
        }

        $amount = $this->fulfillment->costOfGoodsSold($order);

        if ($amount <= 0) {
            return null;
        }

        return $this->journal->postSimple(
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: now()->toDateString(),
            debitAccount: $cogsAccount,
            creditAccount: $inventoryAccount,
            amount: $amount,
            narration: "COGS — Order #{$order->shopify_order_number}",
            referenceType: 'order_cogs',
            referenceId: $order->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    public function postPurchaseEntry(PurchaseReceipt $receipt): ?JournalEntry
    {
        if ((float) $receipt->total_cost <= 0 || $this->journal->hasPostedEntryFor('purchase_receipts', $receipt->id)) {
            return null;
        }

        $inventoryAccount = $this->mapping->inventoryAssetAccount();
        $payableAccount = $this->mapping->payableAccount();

        if (! $inventoryAccount || ! $payableAccount) {
            return null;
        }

        return $this->journal->postSimple(
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: $receipt->receipt_date,
            debitAccount: $inventoryAccount,
            creditAccount: $payableAccount,
            amount: (float) $receipt->total_cost,
            narration: "Purchase receipt {$receipt->receipt_number} — {$receipt->vendor->name}",
            referenceType: 'purchase_receipts',
            referenceId: $receipt->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    public function postVendorPaymentEntry(VendorPayment $payment): ?JournalEntry
    {
        if ((float) $payment->amount <= 0 || $this->journal->hasPostedEntryFor('vendor_payments', $payment->id)) {
            return null;
        }

        $payableAccount = $this->mapping->payableAccount();
        $cashOrBank = $this->cashOrBankFor($payment->method);

        if (! $payableAccount || ! $cashOrBank) {
            return null;
        }

        return $this->journal->postSimple(
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: $payment->payment_date,
            debitAccount: $payableAccount,
            creditAccount: $cashOrBank,
            amount: (float) $payment->amount,
            narration: "Vendor payment — {$payment->vendor->name}",
            referenceType: 'vendor_payments',
            referenceId: $payment->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    /**
     * Mirrors postVendorPaymentEntry() for the customer side: debits Cash/
     * Bank (money received) and credits whichever account the sale itself
     * was booked against — the customer's own linked account if set, else
     * the shared pooled Receivable account — so the same account that was
     * debited at sale time is the one credited down as payments come in.
     */
    public function postCustomerPaymentEntry(OrderPayment $payment): ?JournalEntry
    {
        if ((float) $payment->amount <= 0 || $this->journal->hasPostedEntryFor('order_payments', $payment->id)) {
            return null;
        }

        $receivableAccount = $payment->order->customer?->account ?? $this->mapping->receivableAccount();
        $cashOrBank = $this->cashOrBankFor($payment->method);

        if (! $receivableAccount || ! $cashOrBank) {
            return null;
        }

        return $this->journal->postSimple(
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: $payment->payment_date,
            debitAccount: $cashOrBank,
            creditAccount: $receivableAccount,
            amount: (float) $payment->amount,
            narration: "Customer payment — Order #{$payment->order->shopify_order_number}",
            referenceType: 'order_payments',
            referenceId: $payment->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    public function postExpenseEntry(Expense $expense): ?JournalEntry
    {
        if ((float) $expense->amount <= 0 || $this->journal->hasPostedEntryFor('expenses', $expense->id)) {
            return null;
        }

        $expenseAccount = $expense->category->account ?? $this->mapping->defaultExpenseAccount();
        $cashOrBank = $this->cashOrBankFor($expense->payment_method);

        if (! $expenseAccount || ! $cashOrBank) {
            return null;
        }

        return $this->journal->postSimple(
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: $expense->expense_date,
            debitAccount: $expenseAccount,
            creditAccount: $cashOrBank,
            amount: (float) $expense->amount,
            narration: "Expense — {$expense->category->name}",
            referenceType: 'expenses',
            referenceId: $expense->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    /**
     * Debits Salaries Expense for gross pay + any addition adjustments
     * (bonuses etc. — folded into the same expense line rather than a
     * separate account, to keep this a clean 3-line entry), credits Cash/
     * Bank for the actual net payout, and credits a Payroll Deductions
     * Clearing account for whatever was withheld (advance repayments or
     * other deductions) so the entry balances regardless of how much of
     * gross pay was actually disbursed in cash.
     */
    public function postPayrollEntry(PayrollRun $run): ?JournalEntry
    {
        if ($this->journal->hasPostedEntryFor('payroll_runs', $run->id)) {
            return null;
        }

        $salariesExpense = $this->mapping->salariesExpenseAccount();
        $payrollCash = $this->mapping->payrollCashAccount();

        if (! $salariesExpense || ! $payrollCash) {
            return null;
        }

        $run->loadMissing('items');
        $itemIds = $run->items->pluck('id');

        $grossTotal = (float) $run->items->sum('gross_pay');
        $netTotal = (float) $run->items->sum('net_pay');
        $deductionsTotal = (float) $run->items->sum('total_deductions');
        $additionsTotal = (float) PayrollAdjustment::whereIn('payroll_run_item_id', $itemIds)
            ->where('type', PayrollAdjustment::TYPE_ADDITION)
            ->sum('amount');

        if ($grossTotal + $additionsTotal <= 0) {
            return null;
        }

        $lines = [
            ['account_id' => $salariesExpense->id, 'debit' => $grossTotal + $additionsTotal, 'credit' => 0],
            ['account_id' => $payrollCash->id, 'debit' => 0, 'credit' => $netTotal],
        ];

        if ($deductionsTotal > 0) {
            $deductionsAccount = $this->mapping->payrollDeductionsAccount();

            if ($deductionsAccount) {
                $lines[] = ['account_id' => $deductionsAccount->id, 'debit' => 0, 'credit' => $deductionsTotal];
            } else {
                $lines[1]['credit'] += $deductionsTotal;
            }
        }

        return $this->journal->post(
            lines: $lines,
            type: JournalEntry::TYPE_JOURNAL,
            entryDate: now()->toDateString(),
            narration: "Payroll — {$run->period_start->toDateString()} to {$run->period_end->toDateString()}",
            referenceType: 'payroll_runs',
            referenceId: $run->id,
            source: JournalEntry::SOURCE_SYSTEM,
        );
    }

    private function cashOrBankFor(?string $method): ?Account
    {
        return $method === null || strtolower($method) === 'cash'
            ? $this->mapping->cashAccount()
            : $this->mapping->bankAccount();
    }
}
