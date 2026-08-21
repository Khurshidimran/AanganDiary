@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @can('orders.view')
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <h1 class="h4 mb-0">Dashboard</h1>
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
        <div class="text-muted small mb-3">
            {{ $dateFrom->format('M j, Y') }} – {{ $dateTo->format('M j, Y') }} · KPIs, trend and funnel reflect this range;
            operations and alerts below are always current.
        </div>

        @include('dashboard._kpi-cards')

        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                @include('dashboard._trend-chart')
            </div>
            <div class="col-lg-4">
                @include('dashboard._funnel')
            </div>
        </div>

        @include('dashboard._needs-attention')

        @include('dashboard._todays-operations')

        <div class="row g-3 mb-3">
            <div class="col-lg-7">
                @include('dashboard._recent-orders')
            </div>
            <div class="col-lg-5">
                @include('dashboard._courier-performance')
            </div>
        </div>
    @endcan

    <div class="row g-3">
        <div class="col-sm-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Users</div>
                    <div class="fs-4 fw-bold">{{ $totalUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Active Users</div>
                    <div class="fs-4 fw-bold">{{ $activeUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Roles Defined</div>
                    <div class="fs-4 fw-bold">{{ $totalRoles }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
