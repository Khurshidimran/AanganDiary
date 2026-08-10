<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\OrderFulfillmentService;
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

        $orders = Order::query()
            ->when($request->filled('order_status'), fn ($q) => $q->where('order_status', $request->query('order_status')))
            ->when($request->filled('delivery_status'), fn ($q) => $q->where('delivery_status', $request->query('delivery_status')))
            ->latest('shopify_created_at')
            ->paginate(20)
            ->withQueryString();

        return view('orders.index', compact('orders'));
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
