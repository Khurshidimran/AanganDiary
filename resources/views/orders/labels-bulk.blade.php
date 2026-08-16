<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Delivery Labels ({{ $orders->count() }})</title>
    @include('orders._label-styles')
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:8px;">Print All</button>

    @forelse ($orders as $order)
        @include('orders._label-content', ['order' => $order])
    @empty
        <p>No orders selected.</p>
    @endforelse

    <script>window.onload = () => window.print();</script>
</body>
</html>
