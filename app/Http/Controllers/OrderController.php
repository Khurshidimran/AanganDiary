<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Exports\OrdersExport;
use App\Models\Order;
use App\Services\AuditLogService;
use App\Services\OrderFulfillmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

        [$query, $dateFrom, $dateTo, $isDefaultDateRange] = $this->filteredQuery($request);

        $sort = $request->query('sort') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->query('per_page'), [10, 50, 100], true)
            ? (int) $request->query('per_page')
            : 50;

        // Sum/count across the whole filtered result, not just the current
        // page — cloned before pagination narrows the query to one page.
        $totalSum = (clone $query)->sum('total');
        $totalCount = (clone $query)->count();

        $orders = $query->orderBy('shopify_created_at', $sort)->paginate($perPage)->withQueryString();

        return view('orders.index', compact(
            'orders', 'dateFrom', 'dateTo', 'sort', 'perPage', 'isDefaultDateRange', 'totalSum', 'totalCount',
        ));
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        [$query] = $this->filteredQuery($request);
        $orders = $query->with('items')->orderByDesc('shopify_created_at')->get();

        return Pdf::loadView('orders.report-pdf', compact('orders'))
            ->setPaper('a4', 'landscape')
            ->download('orders-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Order::class);

        [$query] = $this->filteredQuery($request);
        $orders = $query->with('items')->orderByDesc('shopify_created_at')->get();

        return Excel::download(new OrdersExport($orders), 'orders-report-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Shared by the orders list and both export actions so the exported
     * rows always match whatever's currently filtered on screen — same
     * date-default rule as the list (see index()'s original comment):
     * no date_from/date_to at all means "current month"; present-but-blank
     * means the user explicitly asked for all-time.
     *
     * @return array{0: Builder, 1: ?Carbon, 2: ?Carbon, 3: bool}
     */
    private function filteredQuery(Request $request): array
    {
        $isDefaultDateRange = ! $request->has('date_from') && ! $request->has('date_to');

        if ($isDefaultDateRange) {
            $dateFrom = now()->startOfMonth();
            $dateTo = now()->endOfDay();
        } else {
            $dateFrom = $request->filled('date_from') ? Carbon::parse($request->query('date_from'))->startOfDay() : null;
            $dateTo = $request->filled('date_to') ? Carbon::parse($request->query('date_to'))->endOfDay() : null;
        }

        $query = Order::query()
            ->when($request->filled('order_status'), fn ($q) => $q->where('order_status', $request->query('order_status')))
            ->when($request->filled('delivery_status'), fn ($q) => $q->where('delivery_status', $request->query('delivery_status')))
            ->when($dateFrom, fn ($q) => $q->where('shopify_created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('shopify_created_at', '<=', $dateTo));

        return [$query, $dateFrom, $dateTo, $isDefaultDateRange];
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items.productVariant.unit');

        return view('orders.show', compact('order'));
    }

    public function label(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items');

        return view('orders.label', compact('order'));
    }

    public function bulkLabels(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['exists:orders,id'],
        ]);

        $orders = Order::with('items')
            ->whereIn('id', $validated['order_ids'])
            ->orderBy('shopify_created_at')
            ->get();

        return view('orders.labels-bulk', compact('orders'));
    }

    public function confirm(Order $order): RedirectResponse
    {
        $this->authorize('confirm', $order);

        // Confirmation is a business decision independent of inventory —
        // stock is still allocated when possible, but a shortage or missing
        // warehouse config no longer blocks the order from being confirmed.
        $stockError = null;

        try {
            $this->fulfillment->allocateStock($order);
        } catch (InsufficientStockException|WarehouseNotConfiguredException $e) {
            $stockError = $e->getMessage();
        }

        $order->update(['order_status' => Order::ORDER_STATUS_CONFIRMED]);

        $this->auditLog->log('confirmed', 'orders', $order, null, ['order_status' => $order->order_status]);

        return back()->with('status', $stockError
            ? "Order confirmed. Stock was not allocated: {$stockError}"
            : 'Order confirmed and stock allocated.');
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
