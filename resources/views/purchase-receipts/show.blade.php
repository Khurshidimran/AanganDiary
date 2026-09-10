@extends('layouts.app')

@section('title', $purchaseReceipt->receipt_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $purchaseReceipt->receipt_number }}</h1>
        @can('unpost', $purchaseReceipt)
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#unpostModal">
                Unpost
            </button>
        @endcan
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-3">Purchase Order</dt>
                        <dd class="col-sm-9"><a href="{{ route('purchase-orders.show', $purchaseReceipt->purchaseOrder) }}">{{ $purchaseReceipt->purchaseOrder->po_number }}</a></dd>
                        <dt class="col-sm-3">Vendor</dt>
                        <dd class="col-sm-9"><a href="{{ route('vendors.show', $purchaseReceipt->vendor) }}">{{ $purchaseReceipt->vendor->name }}</a></dd>
                        <dt class="col-sm-3">Warehouse</dt>
                        <dd class="col-sm-9">{{ $purchaseReceipt->warehouse->name }}</dd>
                        <dt class="col-sm-3">Receipt Date</dt>
                        <dd class="col-sm-9">{{ $purchaseReceipt->receipt_date->format('Y-m-d') }}</dd>
                        <dt class="col-sm-3">Invoice Number</dt>
                        <dd class="col-sm-9">{{ $purchaseReceipt->invoice_number ?? '—' }}</dd>
                        <dt class="col-sm-3">Received By</dt>
                        <dd class="col-sm-9">{{ $purchaseReceipt->receivedBy?->name ?? '—' }}</dd>
                        <dt class="col-sm-3">Notes</dt>
                        <dd class="col-sm-9">{{ $purchaseReceipt->notes ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Cost</div>
                    <div class="fs-4 fw-bold">{{ number_format($purchaseReceipt->total_cost, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Items Received (posted to stock)</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchaseReceipt->items as $item)
                        <tr>
                            <td>{{ $item->productVariant->product->name }}</td>
                            <td>{{ $item->productVariant->name }} ({{ $item->productVariant->sku }})</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->productVariant->unit->short_code }}</td>
                            <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_cost, 2) }}</td>
                            <td>{{ $item->batch_number ?? '—' }}</td>
                            <td>{{ $item->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @can('unpost', $purchaseReceipt)
        <div class="modal fade" id="unpostModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('purchase-receipts.unpost', $purchaseReceipt) }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Unpost {{ $purchaseReceipt->receipt_number }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">
                                This reverses the stock this receipt added and the accounting entry it posted, then reopens
                                its purchase order so you can fix the quantity or cost and receive it again. If this stock
                                has already been used elsewhere, unposting will be blocked.
                            </p>
                            <label for="unpost-reason" class="form-label">Reason</label>
                            <input id="unpost-reason" type="text" name="reason" class="form-control" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Unpost</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endsection
