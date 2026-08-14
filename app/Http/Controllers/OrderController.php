<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\OrderFulfillmentService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly OrderFulfillmentService $fulfillment,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        // No date_from/date_to in the query string at all means this is the
        // first, unfiltered visit — default to the current month. If they're
        // present but blank, the user explicitly cleared the filter to see
        // all-time orders, so that's left unbounded rather than re-defaulted.
        $isDefaultDateRange = ! $request->has('date_from') && ! $request->has('date_to');

        if ($isDefaultDateRange) {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfDay();
        } else {
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : null;
        }

        $sort = $request->query('sort') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->query('per_page'), [10, 50, 100], true)
            ? (int) $request->query('per_page')
            : 50;

        $orders = Order::query()
            ->when($request->filled('order_status'), fn ($q) => $q->where('order_status', $request->query('order_status')))
            ->when($request->filled('delivery_status'), fn ($q) => $q->where('delivery_status', $request->query('delivery_status')))
            ->when($dateFrom, fn ($q) => $q->where('shopify_created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('shopify_created_at', '<=', $dateTo))
            ->orderBy('shopify_created_at', $sort)
            ->paginate($perPage)
            ->withQueryString();

        return view('orders.index', compact('orders', 'dateFrom', 'dateTo', 'sort', 'perPage', 'isDefaultDateRange'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items.productVariant.unit');

        return view('orders.show', compact('order'));
    }

    public function confirm(Order $order): RedirectResponse
    {
        $this->authorize('confirm', $order);

        try {
            DB::transaction(function () use ($order) {
                $this->fulfillment->allocateStock($order);
                $order->update(['order_status' => Order::ORDER_STATUS_CONFIRMED]);
            });
        } catch (InsufficientStockException|WarehouseNotConfiguredException $e) {
            return back()->with('error', "Cannot confirm order: {$e->getMessage()}");
        }

        $this->auditLog->log('confirmed', 'orders', $order, null, ['order_status' => $order->order_status]);

        return back()->with('status', 'Order confirmed and stock allocated.');
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $wasConfirmed = $order->order_status === Order::ORDER_STATUS_CONFIRMED;

        try {
            DB::transaction(function () use ($order, $wasConfirmed) {
                if ($wasConfirmed) {
                    $this->fulfillment->releaseStock($order);
                }

                $order->update(['order_status' => Order::ORDER_STATUS_CANCELLED]);
            });
        } catch (WarehouseNotConfiguredException $e) {
            return back()->with('error', "Cannot cancel order: {$e->getMessage()}");
        }

        $this->auditLog->log('cancelled', 'orders', $order, null, ['order_status' => $order->order_status]);

        return back()->with('status', 'Order cancelled.');
    }
}
