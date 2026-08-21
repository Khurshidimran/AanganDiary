<?php

namespace App\Http\Controllers;

use App\Models\DeliveryAttempt;
use App\Models\Order;
use App\Models\RiderProfile;
use App\Models\Role;
use App\Models\ShopifySyncLog;
use App\Models\User;
use App\Services\RiderSettlementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly RiderSettlementService $settlement)
    {
    }

    public function index(Request $request): View
    {
        // Defaults to this month, matching the Orders screen's own default
        // date scope — a familiar, consistent window across the app.
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();

        return view('dashboard', array_merge([
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'totalRoles' => Role::count(),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], $this->kpis($dateFrom, $dateTo), [
            'trend' => $this->trend(),
            'funnel' => $this->funnel($dateFrom, $dateTo),
            'needsAttention' => $this->needsAttention(),
            'todaysOperations' => $this->todaysOperations(),
            'liveRiders' => $this->liveRiders(),
            'recentOrders' => Order::with(['channel', 'rider.user'])->latest('shopify_created_at')->take(10)->get(),
            'courierPerformance' => $this->courierPerformance($dateFrom, $dateTo),
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function kpis(Carbon $dateFrom, Carbon $dateTo): array
    {
        $ordersInRange = Order::whereBetween('shopify_created_at', [$dateFrom, $dateTo]);

        // Same-length immediately-preceding window, for a real period-over-
        // period delta on arbitrary custom ranges (not just "this month").
        $periodSeconds = $dateFrom->diffInSeconds($dateTo);
        $prevTo = $dateFrom->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subSeconds($periodSeconds);
        $prevOrdersInRange = Order::whereBetween('shopify_created_at', [$prevFrom, $prevTo]);

        $totalOrders = (clone $ordersInRange)->count();
        $prevTotalOrders = (clone $prevOrdersInRange)->count();

        $delivered = (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count();
        $prevDelivered = (clone $prevOrdersInRange)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count();

        $deliveredOrderIds = (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->pluck('id');
        $firstAttemptDelivered = $deliveredOrderIds->isEmpty() ? 0 : DeliveryAttempt::whereIn('order_id', $deliveredOrderIds)
            ->where('status', Order::DELIVERY_STATUS_DELIVERED)
            ->where('attempt_number', 1)
            ->distinct('order_id')
            ->count('order_id');

        $awaitingFulfillment = (clone $ordersInRange)
            ->where('order_status', Order::ORDER_STATUS_CONFIRMED)
            ->where('delivery_status', Order::DELIVERY_STATUS_PENDING)
            ->count();
        $prevAwaitingFulfillment = (clone $prevOrdersInRange)
            ->where('order_status', Order::ORDER_STATUS_CONFIRMED)
            ->where('delivery_status', Order::DELIVERY_STATUS_PENDING)
            ->count();
        $awaitingPastSchedule = (clone $ordersInRange)
            ->where('order_status', Order::ORDER_STATUS_CONFIRMED)
            ->where('delivery_status', Order::DELIVERY_STATUS_PENDING)
            ->whereNotNull('scheduled_dispatch_at')
            ->where('scheduled_dispatch_at', '<', now())
            ->count();

        // Always live — "what's physically out right now," a snapshot, not
        // a period metric.
        $outForDeliveryQuery = Order::where('delivery_status', Order::DELIVERY_STATUS_OUT_FOR_DELIVERY);
        $outForDelivery = (clone $outForDeliveryQuery)->count();
        $ridersActive = (clone $outForDeliveryQuery)->distinct('rider_id')->count('rider_id');

        // Sourced from DeliveryAttempt, not Order.delivery_status: an order
        // that failed once and was later reassigned/redelivered no longer
        // shows as failed/returned on Order, which would silently undercount
        // real problems that happened this period. distinct('order_id')
        // avoids double-counting an order that failed more than once.
        $exceptionOrderIds = DeliveryAttempt::whereBetween('completed_at', [$dateFrom, $dateTo])
            ->whereIn('status', [Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
            ->distinct('order_id')
            ->pluck('order_id');
        $prevExceptions = DeliveryAttempt::whereBetween('completed_at', [$prevFrom, $prevTo])
            ->whereIn('status', [Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
            ->distinct('order_id')
            ->count('order_id');
        $exceptionsUnresolved = $exceptionOrderIds->isEmpty() ? 0 : Order::whereIn('id', $exceptionOrderIds)
            ->whereIn('delivery_status', [Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
            ->count();

        $revenue = (clone $ordersInRange)->sum('total');
        $codDue = RiderProfile::where('wallet_balance', '>', 0)->sum('wallet_balance');

        return [
            'kpi' => [
                'total_orders' => $totalOrders,
                'total_orders_delta' => $this->percentDelta($totalOrders, $prevTotalOrders),
                'awaiting_fulfillment' => $awaitingFulfillment,
                'awaiting_fulfillment_delta' => $awaitingFulfillment - $prevAwaitingFulfillment,
                'awaiting_past_schedule' => $awaitingPastSchedule,
                'out_for_delivery' => $outForDelivery,
                'riders_active' => $ridersActive,
                'delivered' => $delivered,
                'delivered_delta' => $this->percentDelta($delivered, $prevDelivered),
                'first_attempt_rate' => $delivered > 0 ? round($firstAttemptDelivered / $delivered * 100, 1) : null,
                'exceptions' => $exceptionOrderIds->count(),
                'exceptions_delta' => $exceptionOrderIds->count() - $prevExceptions,
                'exceptions_unresolved' => $exceptionsUnresolved,
                'revenue' => $revenue,
                'cod_due' => $codDue,
            ],
        ];
    }

    private function percentDelta(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 1);
    }

    /**
     * Fixed last-7-days, independent of the date filter — same "always
     * live/near-term" treatment as Today's Operations, not meant to be
     * re-scoped to an arbitrary custom range.
     *
     * @return array{labels: list<string>, orders: list<int>, delivered: list<int>, exceptions: list<int>}
     */
    private function trend(): array
    {
        $days = collect(range(6, 0))->map(fn (int $i) => today()->subDays($i));

        $labels = [];
        $orders = [];
        $delivered = [];
        $exceptions = [];

        foreach ($days as $day) {
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            $labels[] = $day->format('D j/n');
            $orders[] = Order::whereBetween('shopify_created_at', [$start, $end])->count();
            $delivered[] = Order::where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)
                ->whereBetween('delivered_at', [$start, $end])
                ->count();
            $exceptions[] = DeliveryAttempt::whereIn('status', [Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
                ->whereBetween('completed_at', [$start, $end])
                ->distinct('order_id')
                ->count('order_id');
        }

        return compact('labels', 'orders', 'delivered', 'exceptions');
    }

    /**
     * Honors the date filter — a point-in-time snapshot of where this
     * cohort currently sits, so Order's own mutable columns are the
     * correct source here (unlike the exceptions KPI/trend above, which
     * deliberately use DeliveryAttempt instead — see class-level reasoning
     * in the two methods).
     *
     * @return list<array{label: string, count: int, percent: float}>
     */
    private function funnel(Carbon $dateFrom, Carbon $dateTo): array
    {
        $ordersInRange = Order::whereBetween('shopify_created_at', [$dateFrom, $dateTo]);

        $received = (clone $ordersInRange)->count();
        $confirmed = (clone $ordersInRange)->where('order_status', Order::ORDER_STATUS_CONFIRMED)->count();
        $assigned = (clone $ordersInRange)->whereNotNull('assigned_at')->count();
        $dispatched = (clone $ordersInRange)->whereNotNull('picked_up_at')->count();
        $delivered = (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count();
        $returned = (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_RETURNED)->count();

        $percent = fn (int $count) => $received > 0 ? round($count / $received * 100, 1) : 0.0;

        return [
            'stages' => [
                ['label' => 'Orders Received', 'count' => $received, 'percent' => $percent($received)],
                ['label' => 'Confirmed', 'count' => $confirmed, 'percent' => $percent($confirmed)],
                ['label' => 'Assigned', 'count' => $assigned, 'percent' => $percent($assigned)],
                ['label' => 'Dispatched', 'count' => $dispatched, 'percent' => $percent($dispatched)],
                ['label' => 'Delivered', 'count' => $delivered, 'percent' => $percent($delivered)],
                ['label' => 'Returned / RTO', 'count' => $returned, 'percent' => $percent($returned)],
            ],
            'delivered_rate' => $percent($delivered),
            'rto_rate' => $percent($returned),
        ];
    }

    /**
     * Always live — ignores the date filter. Only real, backed conditions;
     * each rendered only if the current user's permissions allow it, and
     * the whole card hides if that leaves nothing to show.
     *
     * @return list<array{message: string, permission: string, route: string}>
     */
    private function needsAttention(): array
    {
        $items = [];

        $pastSchedule = Order::whereNotNull('scheduled_dispatch_at')
            ->where('scheduled_dispatch_at', '<', now())
            ->whereNotIn('delivery_status', [Order::DELIVERY_STATUS_DELIVERED, Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
            ->count();

        if ($pastSchedule > 0) {
            $items[] = [
                'message' => "{$pastSchedule} order".($pastSchedule === 1 ? '' : 's')." past its scheduled dispatch time and still not delivered",
                'action' => 'Reassign',
                'permission' => 'dispatch.view',
                'route' => route('dispatch.index'),
            ];
        }

        $codRiders = RiderProfile::where('wallet_balance', '>', 0);
        $codRidersCount = (clone $codRiders)->count();
        $codTotal = (clone $codRiders)->sum('wallet_balance');

        if ($codRidersCount > 0) {
            $items[] = [
                'message' => "{$codRidersCount} rider".($codRidersCount === 1 ? '' : 's')." holding uncollected COD (Rs. ".number_format($codTotal, 2).' total)',
                'action' => 'Review',
                'permission' => 'rider_wallet.view',
                'route' => route('monitoring.index'),
            ];
        }

        $lastSync = ShopifySyncLog::where('sync_type', ShopifySyncLog::TYPE_ORDERS_PULL)->latest('started_at')->first();

        if ($lastSync && $lastSync->status === ShopifySyncLog::STATUS_FAILED) {
            $items[] = [
                'message' => 'The last Shopify order sync failed'.($lastSync->error_summary ? " — {$lastSync->error_summary}" : ''),
                'action' => 'Retry now',
                'permission' => 'shopify.view',
                'route' => route('shopify.index'),
            ];
        }

        return $items;
    }

    /**
     * Always live — reuses MonitoringController's own today-scoped queries
     * verbatim rather than re-deriving slightly-different SQL, so this
     * panel and the Monitoring page never quietly drift out of sync.
     *
     * @return array<string, mixed>
     */
    private function todaysOperations(): array
    {
        $today = now()->startOfDay();
        $todayOrders = Order::where('shopify_created_at', '>=', $today);

        $inProgress = (clone $todayOrders)->whereIn('delivery_status', [
            Order::DELIVERY_STATUS_ASSIGNED,
            Order::DELIVERY_STATUS_PICKED_UP,
            Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
        ])->count();

        $scheduledToday = Order::whereBetween('scheduled_dispatch_at', [$today, $today->copy()->endOfDay()])
            ->whereNotIn('delivery_status', [Order::DELIVERY_STATUS_DELIVERED, Order::DELIVERY_STATUS_FAILED, Order::DELIVERY_STATUS_RETURNED])
            ->get();

        $deliveredToday = (clone $todayOrders)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count();

        $failedToday = DeliveryAttempt::where('status', Order::DELIVERY_STATUS_FAILED)
            ->whereBetween('completed_at', [$today, $today->copy()->endOfDay()])
            ->distinct('order_id')
            ->count('order_id');

        // Delivery windows — a descriptive histogram over the already-real
        // scheduled_dispatch_at field, not an invented SLA rule.
        $windows = [
            ['label' => '09:00 – 12:00', 'from' => 9, 'to' => 12],
            ['label' => '12:00 – 15:00', 'from' => 12, 'to' => 15],
            ['label' => '15:00 – 18:00', 'from' => 15, 'to' => 18],
            ['label' => '18:00 – 21:00', 'from' => 18, 'to' => 21],
        ];

        $allScheduledToday = Order::whereBetween('scheduled_dispatch_at', [$today, $today->copy()->endOfDay()])->get();

        $deliveryWindows = array_map(function (array $window) use ($allScheduledToday) {
            $inWindow = $allScheduledToday->filter(fn (Order $o) => (int) $o->scheduled_dispatch_at->format('H') >= $window['from']
                && (int) $o->scheduled_dispatch_at->format('H') < $window['to']);

            $done = $inWindow->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED);
            $avgMinutes = $done->isEmpty() ? null : $done
                ->filter(fn (Order $o) => $o->picked_up_at && $o->delivered_at)
                ->avg(fn (Order $o) => $o->picked_up_at->diffInMinutes($o->delivered_at));

            return [
                'label' => $window['label'],
                'done' => $done->count(),
                'total' => $inWindow->count(),
                'avg_minutes' => $avgMinutes !== null ? round($avgMinutes) : null,
            ];
        }, $windows);

        return [
            'dispatched_today' => $inProgress,
            'pending_pickup_today' => $scheduledToday->where('delivery_status', Order::DELIVERY_STATUS_PENDING)->count()
                + $scheduledToday->where('delivery_status', Order::DELIVERY_STATUS_ASSIGNED)->count(),
            'completed_today' => $deliveredToday,
            'failed_today' => $failedToday,
            'windows' => $deliveryWindows,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, RiderProfile>
     */
    private function liveRiders()
    {
        $riders = RiderProfile::with('user')
            ->where('status', RiderProfile::STATUS_ACTIVE)
            ->where(fn ($q) => $q->where('is_checked_in', true)->orWhere('is_online', true))
            ->get();

        $activeOrders = Order::whereIn('rider_id', $riders->pluck('id'))
            ->whereIn('delivery_status', [
                Order::DELIVERY_STATUS_ASSIGNED,
                Order::DELIVERY_STATUS_PICKED_UP,
                Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            ])
            ->get()
            ->groupBy('rider_id');

        $riders->each(fn (RiderProfile $rider) => $rider->setRelation('orders', $activeOrders->get($rider->id, collect())));

        return $riders->sortByDesc(fn (RiderProfile $r) => $r->orders->count())->values();
    }

    /**
     * Top active riders by delivered count in range — reuses
     * RiderSettlementService as-is (no new aggregate SQL), same as the
     * Rider Account page. Bounded, small rider set, so an N-query loop
     * here is acceptable at this operation's scale.
     *
     * @return list<array<string, mixed>>
     */
    private function courierPerformance(Carbon $dateFrom, Carbon $dateTo): array
    {
        $riders = RiderProfile::with('user')->where('status', RiderProfile::STATUS_ACTIVE)->get();

        $rows = $riders->map(function (RiderProfile $rider) use ($dateFrom, $dateTo) {
            $stats = $this->settlement->operationalStats($rider, $dateFrom, $dateTo);
            $financials = $this->settlement->financials($rider, $dateFrom, $dateTo);

            $delivered = $stats['delivered'];

            $firstAttempt = $delivered === 0 ? 0 : DeliveryAttempt::where('rider_id', $rider->id)
                ->where('status', Order::DELIVERY_STATUS_DELIVERED)
                ->where('attempt_number', 1)
                ->whereBetween('delivered_at', [$dateFrom, $dateTo])
                ->count();

            $avgHours = DeliveryAttempt::where('rider_id', $rider->id)
                ->where('status', Order::DELIVERY_STATUS_DELIVERED)
                ->whereBetween('delivered_at', [$dateFrom, $dateTo])
                ->whereNotNull('assigned_at')
                ->avg(DB::raw('TIMESTAMPDIFF(HOUR, assigned_at, delivered_at)'));

            return [
                'rider' => $rider,
                'delivered' => $delivered,
                'first_attempt_rate' => $delivered > 0 ? round($firstAttempt / $delivered * 100, 1) : null,
                'cod_collected' => $financials['cod_collected'],
                'avg_hours' => $avgHours !== null ? round((float) $avgHours, 1) : null,
            ];
        })->filter(fn (array $row) => $row['delivered'] > 0);

        return $rows->sortByDesc('delivered')->take(10)->values()->all();
    }
}
