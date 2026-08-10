@extends('layouts.app')

@section('title', $stockAdjustment->adjustment_number)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $stockAdjustment->adjustment_number }}</h1>
            <span class="badge {{ $stockAdjustment->status === 'posted' ? 'bg-success' : 'bg-secondary' }}">
                {{ ucfirst($stockAdjustment->status) }}
            </span>
        </div>
        <div class="d-flex gap-2">
            @can('update', $stockAdjustment)
                <a href="{{ route('stock-adjustments.edit', $stockAdjustment) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            @endcan
            @can('post', $stockAdjustment)
                <form method="POST" action="{{ route('stock-adjustments.post', $stockAdjustment) }}"
                      onsubmit="return confirm('Post this adjustment? Stock levels will be updated immediately and this cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Post Adjustment</button>
                </form>
            @endcan
            @can('delete', $stockAdjustment)
                <form method="POST" action="{{ route('stock-adjustments.destroy', $stockAdjustment) }}"
                      onsubmit="return confirm('Delete this draft?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">Delete Draft</button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Details</div>
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-2">Warehouse</dt>
                <dd class="col-sm-4">{{ $stockAdjustment->warehouse->name }}</dd>
                <dt class="col-sm-2">Adjustment Date</dt>
                <dd class="col-sm-4">{{ $stockAdjustment->adjustment_date->format('Y-m-d') }}</dd>
                <dt class="col-sm-2">Created By</dt>
                <dd class="col-sm-4">{{ $stockAdjustment->createdBy?->name ?? '—' }}</dd>
                <dt class="col-sm-2">Posted</dt>
                <dd class="col-sm-4">{{ $stockAdjustment->postedBy?->name ? "{$stockAdjustment->postedBy->name} at {$stockAdjustment->posted_at->format('Y-m-d H:i')}" : '—' }}</dd>
                <dt class="col-sm-2">Notes</dt>
                <dd class="col-sm-4">{{ $stockAdjustment->notes ?? '—' }}</dd>
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
                        <th>Reason</th>
                        <th>Direction</th>
                        <th class="text-end">Quantity</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($stockAdjustment->items as $item)
                        <tr>
                            <td>{{ $item->productVariant->product->name }}</td>
                            <td>{{ $item->productVariant->name }} ({{ $item->productVariant->sku }})</td>
                            <td>{{ $item->batch_number ?: '—' }}</td>
                            <td><span class="badge bg-secondary">{{ str($item->reason)->headline() }}</span></td>
                            <td>
                                <span class="badge {{ $item->direction === 'increase' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($item->direction) }}
                                </span>
                            </td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->productVariant->unit->short_code }}</td>
                            <td>{{ $item->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
