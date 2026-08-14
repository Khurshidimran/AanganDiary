@extends('layouts.app')

@section('title', 'Live Monitoring')

@push('meta')
    <meta http-equiv="refresh" content="30">
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Live Monitoring</h1>
        <span class="small text-muted"><i class="bi bi-arrow-repeat"></i> Refreshes every 30s — last updated {{ now()->format('h:i:s A') }}</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $stats['total_today'] }}</div>
                    <div class="small text-muted">Orders Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-secondary">{{ $stats['pending'] }}</div>
                    <div class="small text-muted">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-primary">{{ $stats['confirmed'] }}</div>
                    <div class="small text-muted">Confirmed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-info">{{ $stats['in_progress'] }}</div>
                    <div class="small text-muted">In Progress</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-success">{{ $stats['delivered'] }}</div>
                    <div class="small text-muted">Delivered</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold text-danger">{{ $stats['cancelled'] }}</div>
                    <div class="small text-muted">Cancelled</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Recent Orders</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentOrders as $order)
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
                                    <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                                    <td class="small text-muted">{{ $order->shopify_created_at?->format('h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Recently Cancelled</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Customer</th>
                                <th class="text-end">Total</th>
                                <th>Cancelled At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cancelledOrders as $order)
                                <tr>
                                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                                    <td>{{ $order->customer_name ?? '—' }}</td>
                                    <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                                    <td class="small text-muted">{{ $order->updated_at->format('Y-m-d h:i A') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No cancelled orders.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Rider COD Not Yet Settled</span>
                    <span class="badge bg-warning text-dark fs-6">
                        {{ number_format($codOutstandingTotal, 2) }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Rider</th>
                                <th class="text-end">Amount Held</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($codOutstanding as $rider)
                                <tr>
                                    <td>{{ $rider->user->name }}</td>
                                    <td class="text-end fw-semibold text-warning-emphasis">{{ number_format($rider->wallet_balance, 2) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('riders.wallet', $rider) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">All riders are settled up — no COD outstanding.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
