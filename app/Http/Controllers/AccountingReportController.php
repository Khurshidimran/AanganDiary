<?php

namespace App\Http\Controllers;

use App\Exports\ProfitAndLossExport;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Order;
use App\Models\Vendor;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountingReportController extends Controller
{
    /**
     * Human labels for a journal entry's reference_type — the subtitle under
     * each ledger row's narration (e.g. "Due to supplier" / "Purchase Order").
     */
    private const REFERENCE_TYPE_LABELS = [
        'orders' => 'Sales',
        'order_cogs' => 'Cost of Goods Sold',
        'purchase_receipts' => 'Purchase Order',
        'vendor_payments' => 'Purchase Payment',
        'order_payments' => 'Customer Payment',
        'expenses' => 'Expense',
        'payroll_runs' => 'Payroll',
    ];

    public function ledger(Request $request): View
    {
        $this->authorize('reports.financial.view');

        $accounts = Account::orderBy('code')->get();
        $account = $request->filled('account_id') ? Account::find($request->account_id) : $accounts->first();

        $dateFrom = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', now()->toDateString()));

        $openingBalance = 0.0;
        $rows = collect();

        if ($account) {
            $openingBalance = $account->balanceAsOf($dateFrom->copy()->subDay());

            $lines = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED)
                    ->whereBetween('entry_date', [$dateFrom, $dateTo]))
                ->with('journalEntry')
                ->get()
                ->sortBy(fn ($l) => $l->journalEntry->entry_date->format('Y-m-d').$l->journalEntry->entry_number);

            $running = $openingBalance;
            $isDebitNormal = $account->normalBalance() === 'debit';

            $rows = $lines->map(function (JournalEntryLine $line) use (&$running, $isDebitNormal) {
                $delta = $isDebitNormal ? ((float) $line->debit - (float) $line->credit) : ((float) $line->credit - (float) $line->debit);
                $running += $delta;

                $entry = $line->journalEntry;

                return [
                    'entry' => $entry,
                    'line' => $line,
                    'balance' => $this->drCr($running, $isDebitNormal),
                    'description' => $line->description ?: $entry->narration,
                    'subtitle' => self::REFERENCE_TYPE_LABELS[$entry->reference_type] ?? ($entry->reference_type ? str($entry->reference_type)->headline() : 'Manual Entry'),
                    'reference' => $this->resolveReferenceLabel($entry),
                ];
            });
        }

        return view('reports.ledger', [
            'accounts' => $accounts,
            'account' => $account,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'openingBalance' => $this->drCr($openingBalance, $account?->normalBalance() === 'debit'),
            'rows' => $rows,
            'periodDebit' => $rows->sum(fn ($r) => (float) $r['line']->debit),
            'periodCredit' => $rows->sum(fn ($r) => (float) $r['line']->credit),
            'closingBalance' => $this->drCr(
                $rows->isNotEmpty() ? $rows->last()['balance']['signed'] : $openingBalance,
                $account?->normalBalance() === 'debit',
            ),
        ]);
    }

    /**
     * Converts a normalized (positive = normal-direction) balance into the
     * literal debit/credit side to display — a balance sitting in the
     * account's own normal direction shows as that side (e.g. CR for a
     * liability); one that's gone the other way shows as the opposite side.
     * An exactly-zero balance is shown as "DR" by convention (nothing to
     * attribute a side to yet), matching how most ledgers display a fresh
     * opening balance.
     *
     * @return array{amount: float, label: string, signed: float}
     */
    private function drCr(float $normalizedAmount, ?bool $isDebitNormal): array
    {
        $isDebitNormal ??= true;

        if (abs($normalizedAmount) < 0.005) {
            return ['amount' => 0.0, 'label' => 'DR', 'signed' => $normalizedAmount];
        }

        $onNormalSide = $normalizedAmount >= 0;
        $normalLabel = $isDebitNormal ? 'DR' : 'CR';
        $oppositeLabel = $isDebitNormal ? 'CR' : 'DR';

        return [
            'amount' => abs($normalizedAmount),
            'label' => $onNormalSide ? $normalLabel : $oppositeLabel,
            'signed' => $normalizedAmount,
        ];
    }

    /**
     * Best-effort human reference number for a journal entry's source
     * document — JournalEntry itself carries no such field, so this resolves
     * it from whatever it's actually pointing at.
     */
    private function resolveReferenceLabel(JournalEntry $entry): ?string
    {
        if (! $entry->reference_id) {
            return null;
        }

        return match ($entry->reference_type) {
            'orders', 'order_cogs' => Order::find($entry->reference_id)?->shopify_order_number,
            'purchase_receipts' => \App\Models\PurchaseReceipt::find($entry->reference_id)?->receipt_number,
            'vendor_payments' => \App\Models\VendorPayment::find($entry->reference_id)?->reference_number,
            'order_payments' => \App\Models\OrderPayment::find($entry->reference_id)?->reference_number,
            'expenses' => \App\Models\Expense::find($entry->reference_id)?->reference_number,
            'payroll_runs' => optional(\App\Models\PayrollRun::find($entry->reference_id), fn ($p) => $p->period_start->format('M Y')),
            default => null,
        };
    }

    public function trialBalance(Request $request): View
    {
        $this->authorize('reports.financial.view');

        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        $rows = Account::where('status', Account::STATUS_ACTIVE)
            ->orderBy('code')
            ->get()
            ->map(function (Account $account) use ($asOf) {
                $lines = $account->lines()->whereHas(
                    'journalEntry',
                    fn ($q) => $q->where('status', JournalEntry::STATUS_POSTED)->where('entry_date', '<=', $asOf),
                );

                $debit = (float) $lines->sum('debit');
                $credit = (float) $lines->sum('credit');
                $net = $debit - $credit;

                return [
                    'account' => $account,
                    'debit' => $net > 0 ? $net : 0.0,
                    'credit' => $net < 0 ? -$net : 0.0,
                ];
            })
            ->filter(fn ($row) => $row['debit'] != 0 || $row['credit'] != 0)
            ->values();

        return view('reports.trial-balance', [
            'asOf' => $asOf,
            'rows' => $rows,
            'totalDebit' => $rows->sum('debit'),
            'totalCredit' => $rows->sum('credit'),
        ]);
    }

    /**
     * There's no period-closing step in this app (no "close the books" into
     * Retained Earnings at year-end) — a formally accurate snapshot has to
     * roll cumulative net profit-to-date into Equity itself, or Assets would
     * never actually equal Liabilities + Equity. That's the extra
     * "Current Earnings (unposted)" line below, not a real ledger account.
     */
    public function balanceSheet(Request $request): View
    {
        $this->authorize('reports.financial.view');

        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        $section = fn (string $type) => Account::where('type', $type)->where('status', Account::STATUS_ACTIVE)->orderBy('code')->get()
            ->map(fn (Account $a) => ['account' => $a, 'amount' => $a->balanceAsOf($asOf)])
            ->filter(fn ($r) => $r['amount'] != 0)
            ->values();

        $assets = $section(Account::TYPE_ASSET);
        $liabilities = $section(Account::TYPE_LIABILITY);
        $equity = $section(Account::TYPE_EQUITY);

        $totalRevenue = Account::where('type', Account::TYPE_REVENUE)->where('status', Account::STATUS_ACTIVE)->get()
            ->sum(fn (Account $a) => $a->balanceAsOf($asOf));
        $totalExpense = Account::where('type', Account::TYPE_EXPENSE)->where('status', Account::STATUS_ACTIVE)->get()
            ->sum(fn (Account $a) => $a->balanceAsOf($asOf));
        $currentEarnings = $totalRevenue - $totalExpense;

        $totalAssets = $assets->sum('amount');
        $totalLiabilities = $liabilities->sum('amount');
        $totalEquity = $equity->sum('amount') + $currentEarnings;

        return view('reports.balance-sheet', [
            'asOf' => $asOf,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'currentEarnings' => $currentEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'isBalanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ]);
    }

    public function profitAndLoss(Request $request): View
    {
        $this->authorize('reports.financial.view');

        return view('reports.profit-and-loss', $this->profitAndLossData($request));
    }

    public function profitAndLossPdf(Request $request): Response
    {
        $this->authorize('reports.financial.view');

        $data = $this->profitAndLossData($request);

        return Pdf::loadView('reports.profit-and-loss-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->download('profit-and-loss-'.$data['dateFrom']->format('Y-m-d').'-to-'.$data['dateTo']->format('Y-m-d').'.pdf');
    }

    public function profitAndLossExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('reports.financial.view');

        $data = $this->profitAndLossData($request);

        return Excel::download(
            new ProfitAndLossExport($data['revenueAccounts'], $data['expenseAccounts'], $data['totalRevenue'], $data['totalExpense'], $data['netProfit']),
            'profit-and-loss-'.$data['dateFrom']->format('Y-m-d').'-to-'.$data['dateTo']->format('Y-m-d').'.xlsx',
        );
    }

    /**
     * Shared by the on-screen report and both export actions so the
     * exported figures always match whatever's currently on screen.
     *
     * @return array<string, mixed>
     */
    private function profitAndLossData(Request $request): array
    {
        $dateFrom = Carbon::parse($request->input('date_from', now()->startOfMonth()->toDateString()));
        $dateTo = Carbon::parse($request->input('date_to', now()->toDateString()));

        $revenueAccounts = Account::where('type', Account::TYPE_REVENUE)->where('status', Account::STATUS_ACTIVE)->orderBy('code')->get()
            ->map(fn (Account $a) => ['account' => $a, 'amount' => $a->balanceBetween($dateFrom, $dateTo)])
            ->filter(fn ($r) => $r['amount'] != 0)
            ->values();

        $expenseAccounts = Account::where('type', Account::TYPE_EXPENSE)->where('status', Account::STATUS_ACTIVE)->orderBy('code')->get()
            ->map(fn (Account $a) => ['account' => $a, 'amount' => $a->balanceBetween($dateFrom, $dateTo)])
            ->filter(fn ($r) => $r['amount'] != 0)
            ->values();

        $totalRevenue = $revenueAccounts->sum('amount');
        $totalExpense = $expenseAccounts->sum('amount');

        return [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'revenueAccounts' => $revenueAccounts,
            'expenseAccounts' => $expenseAccounts,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalRevenue - $totalExpense,
        ];
    }

    public function receivablesAging(Request $request): View
    {
        $this->authorize('reports.financial.view');

        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        // Only credit-terms orders are ever a receivable in the first place —
        // a cash/COD order's total_outstanding is just "not collected on
        // delivery yet," not money owed on payment terms. Legacy orders
        // (payment_type defaults to 'cash') correctly stop appearing here as
        // a result, since none of them were ever actually credit sales.
        $orders = Order::where('order_status', '!=', Order::ORDER_STATUS_CANCELLED)
            ->where('payment_type', Order::PAYMENT_TYPE_CREDIT)
            ->where('total_outstanding', '>', 0)
            ->with('customer')
            ->get();

        $rows = $orders->groupBy(fn (Order $o) => $o->customer_id ?? ($o->customer_email ?: $o->customer_name ?: 'Unknown'))
            ->map(function ($group) use ($asOf) {
                $buckets = ['current' => 0.0, 'days_31_60' => 0.0, 'days_61_90' => 0.0, 'over_90' => 0.0];

                foreach ($group as $order) {
                    $age = ($order->shopify_created_at ?? $order->created_at)->diffInDays($asOf, false);
                    $buckets[$this->bucketFor($age)] += (float) $order->total_outstanding;
                }

                // Prefer the linked Customer's name (real FK, dedupes
                // correctly across orders) — only orders predating the
                // Customer feature fall back to the raw email/name string.
                $firstOrder = $group->first();
                $customerName = $firstOrder->customer?->name
                    ?? $firstOrder->customer_email
                    ?? $firstOrder->customer_name
                    ?? 'Unknown';

                return [
                    'customer' => $customerName,
                    'orders' => $group->count(),
                    ...$buckets,
                    'total' => array_sum($buckets),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('reports.receivables-aging', [
            'asOf' => $asOf,
            'rows' => $rows,
            'totals' => $this->sumBuckets($rows),
        ]);
    }

    public function payablesAging(Request $request): View
    {
        $this->authorize('reports.financial.view');

        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()));

        $vendors = Vendor::with(['purchaseReceipts' => fn ($q) => $q->where('receipt_date', '<=', $asOf)->orderBy('receipt_date'), 'payments'])
            ->get()
            ->filter(fn (Vendor $v) => $v->payableBalance() > 0.005);

        $rows = $vendors->map(function (Vendor $vendor) use ($asOf) {
            $buckets = $this->vendorAgingBuckets($vendor, $asOf);

            return [
                'vendor' => $vendor,
                ...$buckets,
                'total' => array_sum($buckets),
            ];
        })->sortByDesc('total')->values();

        return view('reports.payables-aging', [
            'asOf' => $asOf,
            'rows' => $rows,
            'totals' => $this->sumBuckets($rows),
        ]);
    }

    /**
     * FIFO-allocates opening balance + payments against receipts (oldest
     * first) to find the unpaid, aged portion of each — VendorPayment isn't
     * linked to a specific receipt, so this is the standard simplified
     * aging approach when payments aren't invoice-allocated.
     *
     * @return array{current: float, days_31_60: float, days_61_90: float, over_90: float}
     */
    private function vendorAgingBuckets(Vendor $vendor, Carbon $asOf): array
    {
        $buckets = ['current' => 0.0, 'days_31_60' => 0.0, 'days_61_90' => 0.0, 'over_90' => 0.0];

        $remainingPayment = (float) $vendor->payments()->where('payment_date', '<=', $asOf)->sum('amount');

        if ((float) $vendor->opening_balance > 0) {
            $unpaid = max(0, (float) $vendor->opening_balance - $remainingPayment);
            $remainingPayment = max(0, $remainingPayment - (float) $vendor->opening_balance);
            $buckets['over_90'] += $unpaid;
        }

        foreach ($vendor->purchaseReceipts as $receipt) {
            $unpaid = max(0, (float) $receipt->total_cost - $remainingPayment);
            $remainingPayment = max(0, $remainingPayment - (float) $receipt->total_cost);

            if ($unpaid <= 0) {
                continue;
            }

            $age = $receipt->receipt_date->diffInDays($asOf, false);
            $buckets[$this->bucketFor($age)] += $unpaid;
        }

        return $buckets;
    }

    private function bucketFor(int $ageInDays): string
    {
        return match (true) {
            $ageInDays <= 30 => 'current',
            $ageInDays <= 60 => 'days_31_60',
            $ageInDays <= 90 => 'days_61_90',
            default => 'over_90',
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function sumBuckets($rows): array
    {
        return [
            'current' => $rows->sum('current'),
            'days_31_60' => $rows->sum('days_31_60'),
            'days_61_90' => $rows->sum('days_61_90'),
            'over_90' => $rows->sum('over_90'),
            'total' => $rows->sum('total'),
        ];
    }
}
