<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\RiderTrip;
use App\Models\RiderWalletTransaction;
use App\Services\RiderSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The Rider Account / Rider Settlement page — one rider's full operational
 * and financial picture. Read-only; the two write actions (cash deposit,
 * rider payout) live on RiderWalletController, and check-in/deactivate stay
 * on RiderController — this controller only assembles the page and its
 * drill-down views.
 */
class RiderAccountController extends Controller
{
    public function __construct(private readonly RiderSettlementService $settlement)
    {
    }

    public function show(Request $request, RiderProfile $rider): View
    {
        $this->authorize('view', $rider);

        $rider->load('user', 'warehouse');

        [$from, $to, $preset] = $this->resolveDateRange($request);

        $attempts = DeliveryAttempt::with('order')
            // Guards against a delivery-attempt row left behind by an order
            // that no longer exists (e.g. deleted directly in the database,
            // outside the app — there's no order-delete feature in the UI) —
            // without this, rendering the order's number/name below would
            // crash on a null relation instead of just omitting that row.
            ->whereHas('order')
            ->where('rider_id', $rider->id)
            ->when($from && $to, fn ($q) => $q->whereBetween('assigned_at', [$from, $to]))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->query('q');
                $q->whereHas('order', fn ($oq) => $oq
                    ->where('shopify_order_number', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%"));
            })
            ->latest('assigned_at')
            ->paginate(20, ['*'], 'orders_page')
            ->withQueryString();

        // The Orders tab's own status pills/search/pagination fetch this
        // same route via AJAX (X-Requested-With) so filtering it never
        // reloads the whole page — everything else on the page (KPIs,
        // financials, other tabs) stays untouched by that request.
        if ($request->ajax()) {
            return view('riders.account.partials._tab-orders', compact('rider', 'attempts', 'preset', 'from', 'to'));
        }

        $stats = $this->settlement->operationalStats($rider, $from, $to);
        $financials = $this->settlement->financials($rider, $from, $to);
        // Cash Deposit / Pay Rider are real money movements against the
        // rider's true current balance — always all-time, regardless of
        // whatever reporting period is selected elsewhere on this page.
        // Without this, e.g. viewing the default "Today" period would show
        // and validate a deposit against only today's activity, silently
        // rejecting an amount that matches what's on screen if the rider
        // has any older un-deposited cash affecting the real total.
        $allTimeFinancials = $this->settlement->financials($rider);
        $paymentStatus = $this->settlement->earningsPaymentStatus($rider, $from, $to);

        $cashTransactions = $rider->walletTransactions()
            ->whereIn('transaction_type', [
                RiderWalletTransaction::TYPE_COD_COLLECTED,
                RiderWalletTransaction::TYPE_COD_SETTLED,
                RiderWalletTransaction::TYPE_ADJUSTMENT,
            ])
            ->when($from && $to, fn ($q) => $q->whereRaw('COALESCE(transaction_date, DATE(created_at)) BETWEEN ? AND ?', [$from->toDateString(), $to->toDateString()]))
            ->latest('created_at')
            ->paginate(20, ['*'], 'cash_page')
            ->withQueryString();

        $cashOrderNumbers = Order::whereIn('id', $cashTransactions->where('reference_type', 'orders')->pluck('reference_id'))
            ->pluck('shopify_order_number', 'id');

        $earningsAttempts = DeliveryAttempt::with('order')
            ->whereHas('order')
            ->where('rider_id', $rider->id)
            ->where('earning_credited', true)
            ->when($from && $to, fn ($q) => $q->whereBetween('delivered_at', [$from, $to]))
            ->latest('delivered_at')
            ->paginate(20, ['*'], 'earnings_page')
            ->withQueryString();

        $trips = RiderTrip::where('rider_id', $rider->id)
            ->when($from && $to, fn ($q) => $q->whereBetween('checked_in_at', [$from, $to]))
            ->withCount([
                'deliveryAttempts as orders_count',
                'deliveryAttempts as delivered_count' => fn ($q) => $q->where('status', Order::DELIVERY_STATUS_DELIVERED),
                'deliveryAttempts as returned_count' => fn ($q) => $q->where('status', Order::DELIVERY_STATUS_RETURNED),
            ])
            ->latest('checked_in_at')
            ->paginate(20, ['*'], 'trips_page')
            ->withQueryString();

        return view('riders.account.index', compact(
            'rider', 'stats', 'financials', 'allTimeFinancials', 'paymentStatus', 'attempts',
            'cashTransactions', 'cashOrderNumbers', 'earningsAttempts', 'trips',
            'preset', 'from', 'to',
        ));
    }

    /**
     * A single, focused, unified view of everything that ever moved this
     * rider's wallet balance — COD collected, cash deposited, earnings
     * credited, earnings paid out, and manual adjustments — in one
     * chronological list, each showing the order it came from (and that
     * order's type) where there is one. Deliberately separate from the
     * busy multi-tab Rider Account page: that page's Cash Ledger tab only
     * shows cash-related transactions (COD/deposit/adjustment) and splits
     * earnings into a differently-shaped Earnings tab reconstructed from
     * DeliveryAttempt rows rather than the wallet transactions themselves —
     * exactly the kind of split that made the full picture hard to follow.
     * Defaults to all time, not "today", so the opening/closing balance
     * shown is never silently scoped to less than the real running total.
     */
    public function walletLedger(Request $request, RiderProfile $rider): View
    {
        $this->authorize('view', $rider);

        $rider->load('user');

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : null;

        $transactions = $rider->walletTransactions()
            ->when($dateFrom, fn ($q) => $q->whereRaw('COALESCE(transaction_date, DATE(created_at)) >= ?', [$dateFrom->toDateString()]))
            ->when($dateTo, fn ($q) => $q->whereRaw('COALESCE(transaction_date, DATE(created_at)) <= ?', [$dateTo->toDateString()]))
            ->oldest('created_at')
            ->get();

        $orders = Order::whereIn('id', $transactions->where('reference_type', 'orders')->pluck('reference_id'))
            ->get(['id', 'shopify_order_number', 'order_type'])
            ->keyBy('id');

        // Opening = the balance right before this window started — found
        // from the last transaction strictly before it, not the rider's
        // current live balance (which could reflect activity from well
        // after this window and would misrepresent an empty/quiet range).
        if ($transactions->isNotEmpty()) {
            $openingBalance = (float) $transactions->first()->balance_before;
            $closingBalance = (float) $transactions->last()->balance_after;
        } elseif ($dateFrom) {
            $priorTransaction = $rider->walletTransactions()
                ->whereRaw('COALESCE(transaction_date, DATE(created_at)) < ?', [$dateFrom->toDateString()])
                ->latest('created_at')
                ->first();
            $openingBalance = (float) ($priorTransaction->balance_after ?? 0);
            $closingBalance = $openingBalance;
        } else {
            // All-time view with literally zero transactions ever.
            $openingBalance = 0.0;
            $closingBalance = 0.0;
        }

        return view('riders.wallet-ledger', compact('rider', 'transactions', 'orders', 'dateFrom', 'dateTo', 'openingBalance', 'closingBalance'));
    }

    public function showOrderAttempts(RiderProfile $rider, Order $order): View
    {
        $this->authorize('view', $rider);

        $attempts = DeliveryAttempt::where('order_id', $order->id)
            ->orderBy('attempt_number')
            ->with('rider.user')
            ->get();

        return view('riders.account.order-attempts', compact('rider', 'order', 'attempts'));
    }

    public function showTrip(RiderProfile $rider, RiderTrip $trip): View
    {
        $this->authorize('view', $rider);

        $attempts = $trip->deliveryAttempts()->with('order')->whereHas('order')->orderBy('assigned_at')->get();

        return view('riders.account.trip-detail', compact('rider', 'trip', 'attempts'));
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function resolveDateRange(Request $request): array
    {
        $preset = $request->query('period', 'today');

        return match ($preset) {
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), $preset],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek(), $preset],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth(), $preset],
            'custom' => [
                $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : now()->startOfDay(),
                $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : now()->endOfDay(),
                $preset,
            ],
            'all_time' => [null, null, $preset],
            default => [now()->startOfDay(), now()->endOfDay(), 'today'],
        };
    }
}
