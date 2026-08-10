@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Purchase Orders</h1>
        @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Purchase Order
            </a>
        @endcan
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
                                <span class="badge bg-secondary">{{ str($po->status)->headline() }}</span>
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
