<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Manifest</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #000; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .subtitle { font-size: 10px; color: #555; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Delivery Manifest</h1>
    <p class="subtitle">
        Rider: <strong>{{ $rider->user->name }}</strong> ({{ $rider->phone }})
        &nbsp;|&nbsp; Generated {{ now()->format('Y-m-d h:i A') }}
        &nbsp;|&nbsp; {{ $orders->count() }} {{ Str::plural('order', $orders->count()) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Order No</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Delivery Address</th>
                <th>Order Detail</th>
                <th class="text-end">Amount</th>
                <th>Instructions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td>{{ $order->shopify_order_number ?? $order->shopify_order_id }}</td>
                    <td>{{ $order->customer_name ?? '—' }}</td>
                    <td>{{ $order->customer_phone ?? '—' }}</td>
                    <td>{{ $order->formattedAddress() ?? '—' }}</td>
                    <td>{{ $order->itemsSummary() ?: '—' }}</td>
                    <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                    <td>{{ $order->rider_instructions ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #777;">No active deliveries assigned right now.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
