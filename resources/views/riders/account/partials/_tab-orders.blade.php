<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group flex-wrap" role="group">
        @foreach ([
            '' => 'All',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked Up',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'returned' => 'Returned',
        ] as $value => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $value ?: null, 'orders_page' => null]) }}"
               class="btn btn-sm {{ (string) request('status') === (string) $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
    <form method="GET" class="d-flex gap-2">
        <input type="hidden" name="period" value="{{ $preset }}">
        @if ($preset === 'custom')
            <input type="hidden" name="date_from" value="{{ $from?->format('Y-m-d') }}">
            <input type="hidden" name="date_to" value="{{ $to?->format('Y-m-d') }}">
        @endif
        @if (request('status'))
            <input type="hidden" name="status" value="{{ request('status') }}">
        @endif
        <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Order #, customer, or phone" style="min-width: 220px;">
        <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
    </form>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Delivery Attempt</th>
                    <th>Status</th>
                    <th class="text-end">COD Amount</th>
                    <th class="text-end">Delivery Charge</th>
                    <th>Assigned Date</th>
                    <th>Delivered Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attempts as $attempt)
                    <tr>
                        <td>{{ $attempt->order->shopify_order_number ?? $attempt->order->shopify_order_id }}</td>
                        <td>{{ $attempt->order->customer_name ?? '—' }}</td>
                        <td>Attempt #{{ $attempt->attempt_number }}</td>
                        <td>@include('riders.account.partials._status-badge', ['status' => $attempt->status])</td>
                        <td class="text-end">{{ $attempt->cod_amount ? 'Rs. '.number_format($attempt->cod_amount, 2) : '—' }}</td>
                        <td class="text-end">{{ $attempt->delivery_charge ? 'Rs. '.number_format($attempt->delivery_charge, 2) : '—' }}</td>
                        <td>{{ $attempt->assigned_at?->format('d M') ?? '—' }}</td>
                        <td>{{ $attempt->delivered_at?->format('d M') ?? '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('riders.wallet.order-attempts', [$rider, $attempt->order]) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No orders found{{ request('status') || request('q') ? ' matching the current filters' : ' for this rider yet' }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $attempts->links() }}</div>
