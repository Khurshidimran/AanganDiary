<?php

namespace App\Services;

use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes a rider's operational KPIs and financial position for the Rider
 * Account page. Deliberately scopes every operational count off
 * DeliveryAttempt.rider_id, never Order.rider_id: an order's rider_id is a
 * single mutable pointer that moves to whoever holds it right now, so a
 * returned-then-reassigned order would silently vanish from the original
 * rider's stats if counted that way. DeliveryAttempt rows are immutable
 * per-attempt snapshots, so each rider's page only ever reflects attempts
 * they actually handled — nothing double-counted, nothing lost.
 */
class RiderSettlementService
{
    /**
     * @return array{total_orders: int, assigned: int, picked_up: int, out_for_delivery: int, delivered: int, failed: int, returned: int}
     */
    public function operationalStats(RiderProfile $rider, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $base = fn () => DeliveryAttempt::where('rider_id', $rider->id);

        $scoped = fn (string $column) => $base()->when($from && $to, fn ($q) => $q->whereBetween($column, [$from, $to]));

        return [
            'total_orders' => $scoped('assigned_at')->distinct('order_id')->count('order_id'),
            'assigned' => $scoped('assigned_at')->where('status', Order::DELIVERY_STATUS_ASSIGNED)->count(),
            'picked_up' => $scoped('picked_up_at')->where('status', Order::DELIVERY_STATUS_PICKED_UP)->count(),
            'out_for_delivery' => $scoped('out_for_delivery_at')->where('status', Order::DELIVERY_STATUS_OUT_FOR_DELIVERY)->count(),
            'delivered' => $scoped('delivered_at')->where('status', Order::DELIVERY_STATUS_DELIVERED)->count(),
            // Distinct order_id, not attempt count — "Failed"/"Returned" mean
            // unique orders that ever had that outcome with this rider in the
            // period, not a raw attempt tally (an order stat, per the app's
            // own business rule, not a delivery-attempt stat).
            'failed' => $scoped('completed_at')->where('status', Order::DELIVERY_STATUS_FAILED)
                ->distinct('order_id')->count('order_id'),
            'returned' => $scoped('completed_at')->where('status', Order::DELIVERY_STATUS_RETURNED)
                ->distinct('order_id')->count('order_id'),
        ];
    }

    /**
     * When $from/$to are both given, every figure is scoped to that window
     * (transactions dated within it) rather than the rider's all-time
     * ledger — select the "All Time" period on the page to see the true
     * current outstanding balance; any narrower period shows that window's
     * activity instead, which can legitimately differ from what's actually
     * still owed (e.g. an old unsettled balance won't show under "Today").
     *
     * @return array{cod_collected: float, cash_deposited: float, cash_to_hand_in: float, earnings_earned: float, earnings_paid: float, earnings_payable: float, net_position: float}
     */
    public function financials(RiderProfile $rider, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $sum = function (string $type) use ($rider, $from, $to) {
            $query = RiderWalletTransaction::where('rider_id', $rider->id)->where('transaction_type', $type);

            if ($from && $to) {
                $query->whereRaw('COALESCE(transaction_date, DATE(created_at)) BETWEEN ? AND ?', [$from->toDateString(), $to->toDateString()]);
            }

            return (float) $query->sum('amount');
        };

        $codCollected = $sum(RiderWalletTransaction::TYPE_COD_COLLECTED);
        // Stored as negative postings (they reduce the net balance) — flip
        // sign for a positive "amount handed in" / "amount earned" figure.
        $cashDeposited = -$sum(RiderWalletTransaction::TYPE_COD_SETTLED);
        $earningsEarned = -$sum(RiderWalletTransaction::TYPE_EARNING_CREDITED);
        $earningsPaid = $sum(RiderWalletTransaction::TYPE_EARNING_PAID);
        // Adjustments are folded into the cash side by convention (the
        // wallet ledger has no separate cash-vs-earnings tag for them).
        $adjustments = $sum(RiderWalletTransaction::TYPE_ADJUSTMENT);

        $cashToHandIn = $codCollected - $cashDeposited + $adjustments;
        $earningsPayable = $earningsEarned - $earningsPaid;

        return [
            'cod_collected' => $codCollected,
            'cash_deposited' => $cashDeposited,
            'cash_to_hand_in' => $cashToHandIn,
            'earnings_earned' => $earningsEarned,
            'earnings_paid' => $earningsPaid,
            'earnings_payable' => $earningsPayable,
            // Rule: Rider Owes Aangan minus Aangan Owes Rider.
            'net_position' => $cashToHandIn - $earningsPayable,
        ];
    }

    /**
     * Per-delivery "Paid"/"Unpaid" status for the Earnings tab, derived by
     * FIFO: rider payments are lump sums against the aggregate payable, not
     * tied to a specific order, so this walks delivered attempts oldest
     * first and consumes payments in the order they were made — never
     * stored, purely a presentation computed fresh every time (financial
     * balances stay derived-only, never manually editable).
     *
     * Scoped by the same $from/$to window as financials() when given, so the
     * Earnings tab's rows and its "Earnings Payable" summary always agree
     * (sum of unpaid charges shown == earnings_payable for that window).
     *
     * @return Collection<string, array{status: string, paid_at: ?string}> keyed by delivery_attempt_id
     */
    public function earningsPaymentStatus(RiderProfile $rider, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $attempts = DeliveryAttempt::where('rider_id', $rider->id)
            ->where('earning_credited', true)
            ->whereNotNull('delivered_at')
            ->when($from && $to, fn ($q) => $q->whereBetween('delivered_at', [$from, $to]))
            ->orderBy('delivered_at')
            ->get();

        $payments = RiderWalletTransaction::where('rider_id', $rider->id)
            ->where('transaction_type', RiderWalletTransaction::TYPE_EARNING_PAID)
            ->when($from && $to, fn ($q) => $q->whereRaw('COALESCE(transaction_date, DATE(created_at)) BETWEEN ? AND ?', [$from->toDateString(), $to->toDateString()]))
            ->orderByRaw('COALESCE(transaction_date, DATE(created_at))')
            ->get(['amount', 'transaction_date', 'created_at'])
            ->values();

        $result = collect();
        $paymentIndex = 0;
        $paymentRemaining = (float) ($payments->get(0)?->amount ?? 0);

        foreach ($attempts as $attempt) {
            $needed = (float) ($attempt->delivery_charge ?? 0);
            $coveredBy = null;

            if ($needed <= 0.0) {
                $result->put($attempt->id, ['status' => 'paid', 'paid_at' => null]);

                continue;
            }

            while ($needed > 0.001 && $paymentIndex < $payments->count()) {
                $current = $payments->get($paymentIndex);
                $take = min($needed, $paymentRemaining);
                $needed -= $take;
                $paymentRemaining -= $take;
                $coveredBy = $current;

                if ($paymentRemaining <= 0.001) {
                    $paymentIndex++;
                    $paymentRemaining = (float) ($payments->get($paymentIndex)?->amount ?? 0);
                }
            }

            if ($needed <= 0.001) {
                $paidAt = $coveredBy
                    ? ($coveredBy->transaction_date?->toDateString() ?? $coveredBy->created_at->toDateString())
                    : null;
                $result->put($attempt->id, ['status' => 'paid', 'paid_at' => $paidAt]);
            } else {
                $result->put($attempt->id, ['status' => 'unpaid', 'paid_at' => null]);
            }
        }

        return $result;
    }
}
