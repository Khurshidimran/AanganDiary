<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Defaults to this month, matching the Orders screen's own default
        // date scope — a familiar, consistent window across the app.
        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->date_from)->startOfDay() : now()->startOfMonth();
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->date_to)->endOfDay() : now()->endOfDay();

        // Scoped to the intake cohort (orders placed within the range) and
        // reports their current status — there's no separate cancelled_at/
        // returned_at timestamp to report "cancelled/returned within the
        // range" independent of when the order was placed.
        $ordersInRange = Order::whereBetween('shopify_created_at', [$dateFrom, $dateTo]);

        $orderStats = [
            'received' => (clone $ordersInRange)->count(),
            'delivered' => (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_DELIVERED)->count(),
            'cancelled' => (clone $ordersInRange)->where('order_status', Order::ORDER_STATUS_CANCELLED)->count(),
            'returned' => (clone $ordersInRange)->where('delivery_status', Order::DELIVERY_STATUS_RETURNED)->count(),
        ];

        return view('dashboard', [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'active')->count(),
            'totalRoles' => Role::count(),
            'orderStats' => $orderStats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }
}
