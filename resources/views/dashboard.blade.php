@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
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
