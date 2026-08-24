@extends('layouts.app')

@section('title', request()->routeIs('sales.*') ? 'Sales' : 'Orders')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ request()->routeIs('sales.*') ? 'Sales' : 'Orders' }}</h1>
        @can('create', \App\Models\Order::class)
            <a href="{{ route('orders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Order
            </a>
        @endcan
    </div>

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
            <label class="form-label small text-muted mb-1">Channel</label>
            <select name="channel_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Channels</option>
                @foreach ($channels as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('channel_id') === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">Rider</label>
            <select name="rider_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Riders</option>
                @foreach ($riders as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('rider_id') === (string) $id)>{{ $name }}</option>
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
        <div class="col-md-3">
            <label class="form-label small text-muted mb-1">Search</label>
            <div class="input-group input-group-sm">
                <input type="search" name="q" class="form-control" placeholder="Order #, customer, or phone" value="{{ request('q') }}">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
            </div>
        </div>
        <div class="col-md-2">
            @if ($isDefaultDateRange && ! request()->filled('order_status') && ! request()->filled('delivery_status') && ! request()->filled('channel_id') && ! request()->filled('rider_id') && ! request()->filled('q'))
                <span class="small text-muted">Showing this month by default</span>
            @else
                <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset filters</a>
            @endif
        </div>
        <input type="hidden" name="sort" value="{{ $sort }}">
    </form>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $totalCount }}</div>
                    <div class="small text-muted">Order{{ $totalCount === 1 ? '' : 's' }} (Filtered)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $orders->first()->currency ?? 'PKR' }} {{ number_format($totalSum, 2) }}</div>
                    <div class="small text-muted">Total Value</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end gap-2">
            <a href="{{ route('orders.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            <a href="{{ route('orders.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
            <button type="submit" form="labels-bulk-form" formtarget="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer"></i> Print Selected Labels
            </button>
        </div>
    </div>

    <form id="labels-bulk-form" method="GET" action="{{ route('orders.labels.bulk') }}"></form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="select-all-orders" class="form-check-input"></th>
                        <th>Order No</th>
                        <th>Channel</th>
                        <th>Customer Name</th>
                        <th>Customer Contact</th>
                        <th class="text-end">Order Amount</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Delivery Charges</th>
                        <th class="text-end">Total Amount</th>
                        <th>Address</th>
                        <th>Order Detail</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'asc' ? 'desc' : 'asc']) }}"
                               class="text-decoration-none text-body">
                                Order Date
                                <i class="bi {{ $sort === 'asc' ? 'bi-sort-up' : 'bi-sort-down' }}"></i>
                            </a>
                        </th>
                        <th>Order Status</th>
                        <th>Delivery Status</th>
                        <th>Delivered Date/Time</th>
                        <th>Payment Status</th>
                        <th>Rider Name</th>
                        <th>Scheduled Dispatch</th>
                        <th>Instructions for Rider</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                <input type="checkbox" form="labels-bulk-form" name="order_ids[]" value="{{ $order->id }}"
                                       class="form-check-input order-row-checkbox">
                            </td>
                            <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                            <td><span class="badge {{ $order->channel?->code === 'shopify' ? 'bg-success' : 'bg-info text-dark' }}">{{ $order->channel?->name ?? '—' }}</span></td>
                            <td>{{ $order->customer_name ?? '—' }}</td>
                            <td>{{ $order->customer_phone ?? '—' }}</td>
                            <td class="text-end">{{ number_format($order->subtotal, 2) }}</td>
                            <td class="text-end">{{ number_format($order->discount_total, 2) }}</td>
                            <td class="text-end">{{ number_format($order->shipping_total, 2) }}</td>
                            <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                            <td>{{ $order->formattedAddress() ?? '—' }}</td>
                            <td>{{ $order->itemsSummary() ?: '—' }}</td>
                            <td>{{ $order->shopify_created_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match ($order->order_status) {
                                    'confirmed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">{{ ucfirst($order->order_status) }}</span>
                            </td>
                            <td><span class="badge bg-secondary">{{ str($order->delivery_status)->headline() }}</span></td>
                            <td>{{ $order->delivered_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match ($order->payment_status) {
                                    'paid' => 'bg-success',
                                    'refunded' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">{{ str($order->payment_status)->headline() }}</span>
                            </td>
                            <td>{{ $order->rider?->user?->name ?? '—' }}</td>
                            <td>{{ $order->scheduled_dispatch_at?->format('Y-m-d h:i A') ?? '—' }}</td>
                            <td>{{ $order->rider_instructions ?? '—' }}</td>
                            <td>
                                <a href="{{ route('orders.label', $order) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print delivery label">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="20" class="text-center text-muted py-4">No orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>

    @push('scripts')
        <script>
            document.getElementById('select-all-orders')?.addEventListener('change', function () {
                document.querySelectorAll('.order-row-checkbox').forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
            });
        </script>
    @endpush
@endsection
