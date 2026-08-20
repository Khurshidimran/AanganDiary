@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @can('orders.view')
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <h2 class="h6 mb-0">Order Overview</h2>
            <form method="GET" action="{{ route('dashboard') }}" class="d-flex align-items-end gap-2">
                <div>
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
                @unless ($dateFrom->isSameDay(now()->startOfMonth()) && $dateTo->isToday())
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-link">This Month</a>
                @endunless
            </form>
        </div>
        <div class="text-muted small mb-2">
            {{ $dateFrom->format('M j, Y') }} – {{ $dateTo->format('M j, Y') }} · counts reflect each order's current status
        </div>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Orders Received</div>
                        <div class="fs-3 fw-bold">{{ $orderStats['received'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Delivered</div>
                        <div class="fs-3 fw-bold text-success">{{ $orderStats['delivered'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Cancelled</div>
                        <div class="fs-3 fw-bold text-danger">{{ $orderStats['cancelled'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Returned</div>
                        <div class="fs-3 fw-bold text-warning">{{ $orderStats['returned'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Users</div>
                    <div class="fs-3 fw-bold">{{ $totalUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Active Users</div>
                    <div class="fs-3 fw-bold">{{ $activeUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Roles Defined</div>
                    <div class="fs-3 fw-bold">{{ $totalRoles }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <span class="fw-semibold">Coming in later phases</span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-2">
                These panels light up as their modules are built out (Orders, Inventory, Riders, Cash Settlement, Shopify Sync).
            </p>
            <div class="row row-cols-2 row-cols-lg-4 g-3">
                @foreach ([
                    "Today's Orders", 'Pending Orders', 'Ready for Dispatch', 'Active Riders',
                    'Online Riders', 'Delivered Today', 'Failed Deliveries', 'Cash with Riders',
                    'Low Stock Products', 'Expiring Products',
                ] as $label)
                    <div class="col">
                        <div class="border rounded p-3 text-center text-muted">
                            <div class="small">{{ $label }}</div>
                            <div class="fs-5">—</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
