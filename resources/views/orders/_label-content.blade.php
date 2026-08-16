<div class="label">
    <div class="text-center fw-bold" style="font-size: 14px;">{{ config('app.name') }}</div>
    <div class="text-center" style="font-size: 10px;">Delivery Label</div>
    <div class="divider"></div>

    <div class="row-line"><span>Order</span><span class="fw-bold">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</span></div>
    <div class="row-line"><span>Date</span><span>{{ $order->shopify_created_at?->format('d-M-Y h:i A') }}</span></div>
    <div class="divider"></div>

    <div class="fw-bold">{{ $order->customer_name ?? '—' }}</div>
    <div>{{ $order->customer_phone ?? '—' }}</div>
    @if ($order->shipping_address)
        <div>
            {{ $order->shipping_address['address1'] ?? '' }}
            @if (! empty($order->shipping_address['address2']))
                , {{ $order->shipping_address['address2'] }}
            @endif
        </div>
        <div>{{ $order->shipping_address['city'] ?? '' }} {{ $order->shipping_address['province'] ?? '' }}</div>
    @endif
    <div class="divider"></div>

    @foreach ($order->items as $item)
        <div class="row-line">
            <span>{{ $item->product_name }} x{{ (int) $item->quantity }}</span>
            <span>{{ number_format($item->total_price, 0) }}</span>
        </div>
    @endforeach
    <div class="divider"></div>

    <div class="row-line"><span>Order Total</span><span>{{ $order->currency }} {{ number_format($order->total, 2) }}</span></div>

    <div class="cod-box">
        <div style="font-size: 10px;">AMOUNT TO COLLECT (COD)</div>
        <div style="font-size: 18px;">{{ $order->currency }} {{ number_format($order->total_outstanding ?? $order->total, 2) }}</div>
    </div>

    @if ($order->notes)
        <div class="divider"></div>
        <div style="font-size: 10px;"><span class="fw-bold">Note:</span> {{ $order->notes }}</div>
    @endif
</div>
