<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Orders Report</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #000; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .subtitle { font-size: 9px; color: #555; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Orders Report</h1>
    <p class="subtitle">Generated {{ now()->format('Y-m-d h:i A') }} — {{ $orders->count() }} orders</p>

    <table>
        <thead>
            <tr>
                <th>Order No</th>
                <th>Customer</th>
                <th>Contact</th>
                <th class="text-end">Amount</th>
                <th>Address</th>
                <th>Order Detail</th>
                <th>Order Date</th>
                <th>Order Status</th>
                <th>Delivery Status</th>
                <th>Payment Status</th>
                <th>Rider Name</th>
                <th>Scheduled Dispatch</th>
                <th>Instructions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>{{ $order->shopify_order_number ?? $order->shopify_order_id }}</td>
                    <td>{{ $order->customer_name ?? '—' }}</td>
                    <td>{{ $order->customer_phone ?? '—' }}</td>
                    <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                    <td>{{ $order->formattedAddress() ?? '—' }}</td>
                    <td>{{ $order->itemsSummary() ?: '—' }}</td>
                    <td>{{ $order->shopify_created_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                    <td>{{ ucfirst($order->order_status) }}</td>
                    <td>{{ str($order->delivery_status)->headline() }}</td>
                    <td>{{ str($order->payment_status)->headline() }}</td>
                    <td>{{ $order->rider?->user?->name ?? '' }}</td>
                    <td>{{ $order->scheduled_dispatch_at?->format('Y-m-d h:i A') ?? '' }}</td>
                    <td>{{ $order->rider_instructions ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
