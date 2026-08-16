<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Label — {{ $order->shopify_order_number ?? $order->shopify_order_id }}</title>
    @include('orders._label-styles')
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:8px;">Print</button>

    @include('orders._label-content', ['order' => $order])

    <script>window.onload = () => window.print();</script>
</body>
</html>
