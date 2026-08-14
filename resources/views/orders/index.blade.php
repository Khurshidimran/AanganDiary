@extends('layouts.app')

@section('title', 'Orders')

@section('content')
    <h1 class="h4 mb-3">Orders</h1>

    <form method="GET" class="row g-2 mb-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Order Status</label>
            <select name="order_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Order Statuses</option>
                @foreach (['pending', 'confirmed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('order_status') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Delivery Status</label>
            <select name="delivery_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Delivery Statuses</option>
                @foreach (['pending', 'assigned', 'picked_up', 'out_for_delivery', 'delivered', 'failed', 'returned'] as $status)
                    <option value="{{ $status }}" @selected(request('delivery_status') === $status)>{{ str($status)->headline() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">From</label>
            <input type="date" name="date_from" class="form-control form-control-sm"
                   value="{{ $dateFrom?->format('Y-m-d') }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">To</label>
            <input type="date" name="date_to" class="form-control form-control-sm"
                   value="{{ $dateTo?->format('Y-m-d') }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Per Page</label>
            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach ([10, 50, 100] as $option)
                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            @if ($isDefaultDateRange && ! request()->filled('order_status') && ! request()->filled('delivery_status'))
                <span class="small text-muted">Showing this month by default</span>
            @else
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset filters</a>
            @endif
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Shopify Order</th>
                        <th>Customer</th>
                        <th>Order Status</th>
                        <th>Payment Status</th>
                        <th>Delivery Status</th>
                        <th class="text-end">Total</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'asc' ? 'desc' : 'asc']) }}"
                               class="text-decoration-none text-body">
                                Order Date/Time
                                <i class="bi {{ $sort === 'asc' ? 'bi-sort-up' : 'bi-sort-down' }}"></i>
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                            <td>{{ $order->customer_name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match ($order->order_status) {
                                    'confirmed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">{{ ucfirst($order->order_status) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ match ($order->payment_status) {
                                    'paid' => 'bg-success',
                                    'refunded' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">{{ str($order->payment_status)->headline() }}</span>
                            </td>
                            <td><span class="badge bg-secondary">{{ str($order->delivery_status)->headline() }}</span></td>
                            <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->shopify_created_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
@endsection
