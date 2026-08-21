<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\WarehouseNotConfiguredException;
use App\Exports\OrdersExport;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Channel;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\RiderProfile;
use App\Services\AccountingPostingService;
use App\Services\AuditLogService;
use App\Services\OrderFulfillmentService;
use App\Services\Shopify\ShopifyOrderSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly OrderFulfillmentService $fulfillment,
        private readonly ShopifyOrderSyncService $shopifySync,
        private readonly AccountingPostingService $accounting,
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

        $orders = $query->with(['items', 'rider.user', 'channel'])->orderBy('shopify_created_at', $sort)->paginate($perPage)->withQueryString();

        return view('orders.index', compact(
            'orders', 'dateFrom', 'dateTo', 'sort', 'perPage', 'isDefaultDateRange', 'totalSum', 'totalCount',
        ) + [
            'channels' => Channel::orderBy('name')->pluck('name', 'id'),
            'riders' => RiderProfile::with('user')->get()->sortBy(fn (RiderProfile $r) => $r->user->name)->pluck('user.name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Order::class);

        return view('orders.create', [
            // Shopify-origin channels (Shopify itself, Online Store, Draft
            // Orders, the COD form app — all is_system, only ever arrive via
            // sync) are reserved so staff can't accidentally attribute a
            // manually-punched-in phone/WhatsApp order to one of them here.
            'channels' => Channel::where('status', Channel::STATUS_ACTIVE)->where('is_system', false)->orderBy('name')->get(),
            'variants' => ProductVariant::with('product')->where('is_active', true)->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated) {
            $subtotal = collect($validated['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $discountTotal = (float) ($validated['discount_total'] ?? 0);
            $taxTotal = (float) ($validated['tax_total'] ?? 0);
            $shippingTotal = (float) ($validated['shipping_total'] ?? 0);
            $total = $subtotal - $discountTotal + $taxTotal + $shippingTotal;

            $customer = ! empty($validated['customer_id'])
                ? Customer::findOrFail($validated['customer_id'])
                : new Customer();

            // Kept in sync with whatever staff just confirmed/edited on
            // screen even when reusing an existing customer — the order
            // form is the source of truth for "who this order is for right
            // now," same as a fresh Customer's fields.
            $customer->fill([
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'email' => $validated['customer_email'] ?? null,
            ])->save();

            $customerAddress = ! empty($validated['customer_address_id'])
                ? CustomerAddress::findOrFail($validated['customer_address_id'])
                : $customer->addresses()->create([
                    'address1' => $validated['address1'],
                    'address2' => $validated['address2'] ?? null,
                    'city' => $validated['city'],
                    'country' => $validated['country'],
                    'phone' => $validated['customer_phone'],
                    'is_default' => $customer->addresses()->count() === 0,
                ]);

            $address = [
                'name' => $validated['customer_name'],
                'address1' => $customerAddress->address1,
                'address2' => $customerAddress->address2,
                'city' => $customerAddress->city,
                'country' => $customerAddress->country,
                'phone' => $validated['customer_phone'],
            ];

            $order = Order::create([
                'shopify_order_id' => 'local-'.Str::uuid(),
                'shopify_order_number' => $this->nextLocalOrderNumber(),
                'channel_id' => $validated['channel_id'],
                'customer_id' => $customer->id,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'billing_address' => $address,
                'shipping_address' => $address,
                'order_status' => Order::ORDER_STATUS_PENDING,
                'payment_status' => $validated['payment_status'],
                'delivery_status' => Order::DELIVERY_STATUS_PENDING,
                'currency' => 'PKR',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'shipping_total' => $shippingTotal,
                'total' => $total,
                // No partial-payment amount is captured on this simple entry
                // form — "paid" clears the balance, anything else leaves the
                // full total outstanding, same as an unpaid Shopify order.
                'total_outstanding' => $validated['payment_status'] === Order::PAYMENT_STATUS_PAID ? 0 : $total,
                'notes' => $validated['notes'] ?? null,
                'shopify_created_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $variant = ProductVariant::findOrFail($item['product_variant_id']);

                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'product_name' => $variant->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }

            return $order;
        });

        $this->auditLog->log('created', 'orders', $order, null, ['shopify_order_number' => $order->shopify_order_number]);

        return redirect()->route('orders.show', $order)
            ->with('status', "Order {$order->shopify_order_number} created as pending — confirm it below when you're ready to allocate stock.");
    }

    private function nextLocalOrderNumber(): string
    {
        $next = Order::withTrashed()->where('shopify_order_id', 'like', 'local-%')->count() + 1;

        return 'LOCAL-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', Order::class);

        [$query] = $this->filteredQuery($request);
        $orders = $query->with(['items', 'rider.user'])->orderByDesc('shopify_created_at')->get();

        return Pdf::loadView('orders.report-pdf', compact('orders'))
            ->setPaper('a4', 'landscape')
            ->download('orders-report-'.now()->format('Y-m-d').'.pdf');
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Order::class);

        [$query] = $this->filteredQuery($request);
        $orders = $query->with(['items', 'rider.user'])->orderByDesc('shopify_created_at')->get();

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
            ->when($request->filled('channel_id'), fn ($q) => $q->where('channel_id', $request->query('channel_id')))
            ->when($request->filled('rider_id'), fn ($q) => $q->where('rider_id', $request->query('rider_id')))
            // Not a UI filter of its own — set by drill-through links (e.g.
            // the Orders-by-Rider report) so the linked list matches exactly
            // what that report counted, which excludes cancelled orders.
            ->when($request->boolean('exclude_cancelled'), fn ($q) => $q->where('order_status', '!=', Order::ORDER_STATUS_CANCELLED))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->query('q');
                $q->where(fn ($oq) => $oq
                    ->where('shopify_order_number', 'like', "%{$term}%")
                    ->orWhere('shopify_order_id', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhere('customer_phone', 'like', "%{$term}%"));
            })
            ->when($dateFrom, fn ($q) => $q->where('shopify_created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('shopify_created_at', '<=', $dateTo));

        return [$query, $dateFrom, $dateTo, $isDefaultDateRange];
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items.productVariant.unit', 'channel', 'auditLogs');

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

        $salesEntry = $this->accounting->postSalesEntry($order);
        $accountingNote = $salesEntry ? '' : ' Accounting entry was not posted — finish Account Mapping setup.';

        return back()->with('status', ($stockError
            ? "Order confirmed. Stock was not allocated: {$stockError}"
            : 'Order confirmed and stock allocated.').$accountingNote);
    }

    public function cancel(Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        // Cancel on Shopify's side first — if this fails, the local
        // cancellation doesn't proceed either, so the two systems never
        // drift out of sync (deliberate: no "cancelled here but still open
        // on Shopify" state). Manually-entered orders (phone/WhatsApp/etc.)
        // never existed on Shopify, so there's nothing to cancel there.
        if ($order->isFromShopify()) {
            try {
                $this->shopifySync->cancelInShopify($order);
            } catch (Throwable $e) {
                return back()->with('error', "Cannot cancel order: Shopify cancellation failed — {$e->getMessage()}");
            }
        }

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

        if ($wasConfirmed) {
            $this->accounting->voidSalesEntry($order, 'Order cancelled');
        }

        $this->auditLog->log('cancelled', 'orders', $order, null, ['order_status' => $order->order_status]);

        return back()->with('status', 'Order cancelled — locally and in Shopify.');
    }
}
