@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', $type === 'returned' ? 'Returned Orders' : 'Delivered Orders')

@section('content')
    <h1 class="h5 mb-3">{{ $type === 'returned' ? 'Returned Orders' : 'Delivered Orders' }}</h1>
    <div class="text-muted small mb-3">{{ $dateFrom->format('d M Y') }} &mdash; {{ $dateTo->format('d M Y') }}</div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Rider</th>
                        <th>{{ $type === 'returned' ? 'Returned At' : 'Delivered At' }}</th>
                        @if ($type === 'returned')
                            <th>Reason</th>
                        @endif
                        <th class="text-end">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $attempt)
                        <tr>
                            <td><a href="{{ route('orders.show', $attempt->order) }}" target="_top">{{ $attempt->order->shopify_order_number }}</a></td>
                            <td>{{ $attempt->order->customer_name ?? '—' }}</td>
                            <td>{{ $attempt->rider?->user?->name ?? '—' }}</td>
                            <td class="text-nowrap">{{ $attempt->completed_at?->format('d-M h:i A') }}</td>
                            @if ($type === 'returned')
                                <td class="text-muted small">{{ $attempt->return_reason ?? '—' }}</td>
                            @endif
                            <td class="text-end">{{ number_format($attempt->order->total ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $type === 'returned' ? 6 : 5 }}" class="text-center text-muted py-4">No orders in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="{{ $type === 'returned' ? 5 : 4 }}">Total</td>
                        <td class="text-end">{{ number_format($rows->sum(fn ($a) => $a->order->total ?? 0), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
