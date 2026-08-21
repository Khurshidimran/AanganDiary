<div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Recent Orders</span>
        <a href="{{ route('orders.index') }}" class="small">View all orders <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Channel</th>
                    <th class="text-end">Total</th>
                    <th>Payment</th>
                    <th>Rider</th>
                    <th>Status</th>
                    <th>Placed</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    @php
                        $badge = match ($order->delivery_status) {
                            'pending' => ['bg-danger-subtle text-danger-emphasis border border-danger-subtle', 'Pending'],
                            'failed' => ['bg-danger text-white', 'Failed'],
                            'assigned' => ['bg-secondary-subtle text-secondary-emphasis border', 'Assigned'],
                            'picked_up' => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'Picked Up'],
                            'out_for_delivery' => ['bg-primary-subtle text-primary-emphasis border border-primary-subtle', 'Out for delivery'],
                            'delivered' => ['bg-success-subtle text-success-emphasis border border-success-subtle', 'Delivered'],
                            'returned' => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'Returned'],
                            default => ['bg-light text-dark border', str($order->delivery_status)->headline()],
                        };
                    @endphp
                    <tr>
                        <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                        <td>{{ $order->customer_name ?? '—' }}</td>
                        <td><span class="badge {{ $order->channel?->code === 'shopify' ? 'bg-success' : 'bg-info text-dark' }}">{{ $order->channel?->name ?? '—' }}</span></td>
                        <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                        <td class="small">{{ str($order->payment_status)->headline() }}</td>
                        <td class="small">{{ $order->rider?->user?->name ?? '—' }}</td>
                        <td><span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span></td>
                        <td class="small text-muted">{{ $order->shopify_created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No orders yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
