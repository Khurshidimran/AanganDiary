@extends('layouts.app')

@section('title', 'Orders by Rider')

@section('content')
    <h1 class="h4 mb-3">Orders by Rider</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('riders.report') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @unless ($dateFrom->isSameDay(now()->startOfMonth()) && $dateTo->isToday())
                    <div class="col-md-2">
                        <a href="{{ route('riders.report') }}" class="btn btn-sm btn-link">This Month</a>
                    </div>
                @endunless
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $grandTotalOrders }}</div>
                    <div class="small text-muted">Total Orders Assigned</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ number_format($grandTotalAmount, 2) }}</div>
                    <div class="small text-muted">Total Order Value</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Rider</th>
                        <th class="text-end">Total Orders</th>
                        <th class="text-end">Assigned</th>
                        <th class="text-end">Pickup</th>
                        <th class="text-end">Out for Delivery</th>
                        <th class="text-end">Delivered</th>
                        <th class="text-end">Failed</th>
                        <th class="text-end">Returned</th>
                        <th class="text-end">Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['rider']?->user->name ?? 'Unknown' }}</td>
                            <td class="text-end fw-semibold">{{ $row['total_orders'] }}</td>
                            <td class="text-end">{{ $row['assigned'] }}</td>
                            <td class="text-end">{{ $row['picked_up'] }}</td>
                            <td class="text-end">{{ $row['out_for_delivery'] }}</td>
                            <td class="text-end text-success">{{ $row['delivered'] }}</td>
                            <td class="text-end {{ $row['failed'] > 0 ? 'text-danger' : '' }}">{{ $row['failed'] }}</td>
                            <td class="text-end {{ $row['returned'] > 0 ? 'text-warning' : '' }}">{{ $row['returned'] }}</td>
                            <td class="text-end">{{ number_format($row['total_amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No orders were assigned to any rider in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
