@extends('layouts.app')

@section('title', $stockTransfer->transfer_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $stockTransfer->transfer_number }}</h1>
            <span class="badge bg-secondary">{{ str($stockTransfer->status)->headline() }}</span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $stockTransfer)
                <a href="{{ route('stock-transfers.edit', $stockTransfer) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endcan
            @can('request', $stockTransfer)
                <form method="POST" action="{{ route('stock-transfers.request', $stockTransfer) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Request Approval</button>
                </form>
            @endcan
            @can('approve', $stockTransfer)
                <form method="POST" action="{{ route('stock-transfers.approve', $stockTransfer) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                </form>
            @endcan
            @can('dispatch', $stockTransfer)
                <form method="POST" action="{{ route('stock-transfers.dispatch', $stockTransfer) }}"
                      onsubmit="return confirm('Dispatch this transfer? Stock will be deducted from the source warehouse.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Dispatch</button>
                </form>
            @endcan
            @can('receive', $stockTransfer)
                <form method="POST" action="{{ route('stock-transfers.receive', $stockTransfer) }}"
                      onsubmit="return confirm('Confirm receipt? Stock will be added to the destination warehouse.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Confirm Receipt</button>
                </form>
            @endcan
            @can('cancel', $stockTransfer)
                <form method="POST" action="{{ route('stock-transfers.cancel', $stockTransfer) }}"
                      onsubmit="return confirm('Cancel this transfer?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Details</div>
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-2">From</dt>
                <dd class="col-sm-4">{{ $stockTransfer->fromWarehouse->name }}</dd>
                <dt class="col-sm-2">To</dt>
                <dd class="col-sm-4">{{ $stockTransfer->toWarehouse->name }}</dd>
                <dt class="col-sm-2">Transfer Date</dt>
                <dd class="col-sm-4">{{ $stockTransfer->transfer_date->format('Y-m-d') }}</dd>
                <dt class="col-sm-2">Created By</dt>
                <dd class="col-sm-4">{{ $stockTransfer->createdBy?->name ?? '—' }}</dd>
                <dt class="col-sm-2">Approved By</dt>
                <dd class="col-sm-4">{{ $stockTransfer->approvedBy?->name ?? '—' }}</dd>
                <dt class="col-sm-2">Dispatched</dt>
                <dd class="col-sm-4">{{ $stockTransfer->dispatchedBy?->name ? "{$stockTransfer->dispatchedBy->name} at {$stockTransfer->dispatched_at->format('Y-m-d H:i')}" : '—' }}</dd>
                <dt class="col-sm-2">Received</dt>
                <dd class="col-sm-4">{{ $stockTransfer->receivedBy?->name ? "{$stockTransfer->receivedBy->name} at {$stockTransfer->received_at->format('Y-m-d H:i')}" : '—' }}</dd>
                <dt class="col-sm-2">Notes</dt>
                <dd class="col-sm-4">{{ $stockTransfer->notes ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Items</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Batch</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockTransfer->items as $item)
                        <tr>
                            <td>{{ $item->productVariant->product->name }}</td>
                            <td>{{ $item->productVariant->name }} ({{ $item->productVariant->sku }})</td>
                            <td>{{ $item->batch_number ?: '—' }}</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->productVariant->unit->short_code }}</td>
                            <td class="text-end">{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="text-end">{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Total Value</th>
                        <th class="text-end">{{ number_format($stockTransfer->items->sum(fn ($i) => $i->quantity * $i->unit_cost), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
