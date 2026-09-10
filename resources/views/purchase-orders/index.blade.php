@extends('layouts.app')

@section('title', 'Purchase Orders')

@php
    $statusMeta = [
        \App\Models\PurchaseOrder::STATUS_DRAFT => ['label' => 'Draft', 'badge' => 'bg-secondary'],
        \App\Models\PurchaseOrder::STATUS_SUBMITTED => ['label' => 'Submitted', 'badge' => 'bg-info text-dark'],
        \App\Models\PurchaseOrder::STATUS_APPROVED => ['label' => 'Approved', 'badge' => 'bg-primary'],
        \App\Models\PurchaseOrder::STATUS_PARTIALLY_RECEIVED => ['label' => 'Partially Received', 'badge' => 'bg-warning text-dark'],
        \App\Models\PurchaseOrder::STATUS_FULLY_RECEIVED => ['label' => 'Fully Received', 'badge' => 'bg-success'],
        \App\Models\PurchaseOrder::STATUS_CANCELLED => ['label' => 'Cancelled', 'badge' => 'bg-danger'],
    ];
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Purchase Orders</h1>
        @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        @endcan
    </div>

    <div class="row g-2 mb-3">
        @foreach ($statusMeta as $status => $meta)
            @php $s = $summary[$status] ?? ['count' => 0, 'amount' => 0]; @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <span class="badge {{ $meta['badge'] }} mb-1">{{ $meta['label'] }}</span>
                        <div class="fs-5 fw-bold">{{ $s['count'] }}</div>
                        <div class="small text-muted">Rs. {{ number_format($s['amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('purchase-orders.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        @foreach ($statusMeta as $status => $meta)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    @if ($isDefaultDateRange && ! request()->filled('status'))
                        <span class="small text-muted">Showing today by default</span>
                    @else
                        <a href="{{ route('purchase-orders.index') }}" class="btn btn-sm btn-link">Reset filters</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Vendor</th>
                        <th>Warehouse</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $po)
                        <tr>
                            <td><a href="{{ route('purchase-orders.show', $po) }}">{{ $po->po_number }}</a></td>
                            <td>{{ $po->vendor->name }}</td>
                            <td>{{ $po->warehouse->name }}</td>
                            <td>{{ $po->order_date->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge {{ $statusMeta[$po->status]['badge'] ?? 'bg-secondary' }}">{{ $statusMeta[$po->status]['label'] ?? str($po->status)->headline() }}</span>
                            </td>
                            <td class="text-end">{{ number_format($po->totalCost(), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $purchaseOrders->links() }}
    </div>
@endsection
