@extends('layouts.app')

@section('title', $purchaseOrder->po_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $purchaseOrder->po_number }}</h1>
            <span class="badge bg-secondary">{{ str($purchaseOrder->status)->headline() }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $purchaseOrder)
                <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endcan
            @can('submit', $purchaseOrder)
                <form method="POST" action="{{ route('purchase-orders.submit', $purchaseOrder) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Submit for Approval</button>
                </form>
            @endcan
            @can('approve', $purchaseOrder)
                <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                </form>
            @endcan
            @can('cancel', $purchaseOrder)
                <form method="POST" action="{{ route('purchase-orders.cancel', $purchaseOrder) }}"
                      onsubmit="return confirm('Cancel this purchase order?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                </form>
            @endcan
            @if ($purchaseOrder->canReceiveStock())
                @can('create', \App\Models\PurchaseReceipt::class)
                    <a href="{{ route('purchase-receipts.create', ['purchase_order' => $purchaseOrder->id]) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-box-arrow-in-down"></i> Receive Stock
                    </a>
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-3">Vendor</dt>
                        <dd class="col-sm-9"><a href="{{ route('vendors.show', $purchaseOrder->vendor) }}">{{ $purchaseOrder->vendor->name }}</a></dd>
                        <dt class="col-sm-3">Warehouse</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->warehouse->name }}</dd>
                        <dt class="col-sm-3">Order Date</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->order_date->format('Y-m-d') }}</dd>
                        <dt class="col-sm-3">Expected Date</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->expected_date?->format('Y-m-d') ?? '—' }}</dd>
                        <dt class="col-sm-3">Created By</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->createdBy?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Notes</dt>
                        <dd class="col-sm-9">{{ $purchaseOrder->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Value</div>
                    <div class="fs-4 fw-bold">{{ number_format($purchaseOrder->totalCost(), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Items</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th class="text-end">Ordered</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Remaining</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->productVariant->product->name }}</td>
                            <td>{{ $item->productVariant->name }} ({{ $item->productVariant->sku }})</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity_ordered, '0'), '.') }} {{ $item->productVariant->unit->short_code }}</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity_received, '0'), '.') }}</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantityRemaining(), '0'), '.') }}</td>
                            <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="text-end">{{ number_format($item->quantity_ordered * $item->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Goods Receipts</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Receipt Number</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrder->receipts as $receipt)
                        <tr>
                            <td><a href="{{ route('purchase-receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ number_format($receipt->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No goods received yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
