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
                        @php
                            $baseParams = [
                                'rider_id' => $row['rider']?->id,
                                'date_from' => $dateFrom->format('Y-m-d'),
                                'date_to' => $dateTo->format('Y-m-d'),
                                'exclude_cancelled' => 1,
                            ];
                        @endphp
                        <tr>
                            <td>{{ $row['rider']?->user->name ?? 'Unknown' }}</td>
                            <td class="text-end fw-semibold">
                                @if ($row['rider'] && $row['total_orders'] > 0)
                                    <a href="{{ route('orders.index', $baseParams) }}">{{ $row['total_orders'] }}</a>
                                @else
                                    {{ $row['total_orders'] }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($row['rider'] && $row['assigned'] > 0)
                                    <a href="{{ route('orders.index', $baseParams + ['delivery_status' => 'assigned']) }}">{{ $row['assigned'] }}</a>
                                @else
                                    {{ $row['assigned'] }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($row['rider'] && $row['picked_up'] > 0)
                                    <a href="{{ route('orders.index', $baseParams + ['delivery_status' => 'picked_up']) }}">{{ $row['picked_up'] }}</a>
                                @else
                                    {{ $row['picked_up'] }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($row['rider'] && $row['out_for_delivery'] > 0)
                                    <a href="{{ route('orders.index', $baseParams + ['delivery_status' => 'out_for_delivery']) }}">{{ $row['out_for_delivery'] }}</a>
                                @else
                                    {{ $row['out_for_delivery'] }}
                                @endif
                            </td>
                            <td class="text-end text-success">
                                @if ($row['rider'] && $row['delivered'] > 0)
                                    <a href="{{ route('orders.index', $baseParams + ['delivery_status' => 'delivered']) }}">{{ $row['delivered'] }}</a>
                                @else
                                    {{ $row['delivered'] }}
                                @endif
                            </td>
                            <td class="text-end {{ $row['failed'] > 0 ? 'text-danger' : '' }}">
                                @if ($row['rider'] && $row['failed'] > 0)
                                    <a class="text-danger" href="{{ route('orders.index', $baseParams + ['delivery_status' => 'failed']) }}">{{ $row['failed'] }}</a>
                                @else
                                    {{ $row['failed'] }}
                                @endif
                            </td>
                            <td class="text-end {{ $row['returned'] > 0 ? 'text-warning' : '' }}">
                                @if ($row['rider'] && $row['returned'] > 0)
                                    <a class="text-warning" href="{{ route('orders.index', $baseParams + ['delivery_status' => 'returned']) }}">{{ $row['returned'] }}</a>
                                @else
                                    {{ $row['returned'] }}
                                @endif
                            </td>
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
