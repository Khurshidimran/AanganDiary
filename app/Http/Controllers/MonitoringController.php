<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RiderProfile;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    public function index(): View
    {
        $this->authorize('monitoring.view');

        $today = now()->startOfDay();
        $todayOrders = Order::where('shopify_created_at', '>=', $today);

        $stats = [
            'total_today' => (clone $todayOrders)->count(),
            'pending' => (clone $todayOrders)->where('order_status', Order::ORDER_STATUS_PENDING)->count(),
            'confirmed' => (clone $todayOrders)->where('order_status', Order::ORDER_STATUS_CONFIRMED)->count(),
            'cancelled' => (clone $todayOrders)->where('order_status', Order::ORDER_STATUS_CANCELLED)->count(),
            'delivered' => (clone $todayOrders)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count(),
            'in_progress' => (clone $todayOrders)->whereIn('delivery_status', [
                Order::DELIVERY_STATUS_ASSIGNED,
                Order::DELIVERY_STATUS_PICKED_UP,
                Order::DELIVERY_STATUS_OUT_FOR_DELIVERY,
            ])->count(),
        ];

        $recentOrders = Order::latest('shopify_created_at')->take(10)->get();

        $cancelledOrders = Order::where('order_status', Order::ORDER_STATUS_CANCELLED)
            ->latest('updated_at')
            ->take(10)
            ->get();

        // "Going to be delivered today" — scheduled for today and not
        // already finished, so this list only shows what still needs
        // attention rather than growing to include today's completed ones.
        $scheduledToday = Order::with('rider.user')
            ->whereBetween('scheduled_dispatch_at', [$today, $today->copy()->endOfDay()])
            ->whereNotIn('delivery_status', [
                Order::DELIVERY_STATUS_DELIVERED,
                Order::DELIVERY_STATUS_FAILED,
                Order::DELIVERY_STATUS_RETURNED,
            ])
            ->orderBy('scheduled_dispatch_at')
            ->get();

        $outForDelivery = Order::with('rider.user')
            ->where('delivery_status', Order::DELIVERY_STATUS_OUT_FOR_DELIVERY)
            ->orderBy('assigned_at')
            ->get();

        $deliveredToday = Order::with('rider.user')
            ->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)
            ->whereDate('delivered_at', $today)
            ->latest('delivered_at')
            ->get();

        // Positive wallet_balance means the rider is holding COD cash they've
        // collected but haven't handed over to admin yet (see RiderWalletService
        // — COD_COLLECTED credits the balance, COD_SETTLED debits it back down).
        $codOutstanding = RiderProfile::with('user')
            ->where('wallet_balance', '>', 0)
            ->orderByDesc('wallet_balance')
            ->get();

        $codOutstandingTotal = $codOutstanding->sum('wallet_balance');

        return view('monitoring.index', compact(
            'stats', 'recentOrders', 'cancelledOrders', 'codOutstanding', 'codOutstandingTotal',
            'scheduledToday', 'outForDelivery', 'deliveredToday',
        ));
    }
}
