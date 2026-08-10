@extends('layouts.app')

@section('title', 'Stock Adjustments')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Stock Adjustments</h1>
        @can('create', \App\Models\StockAdjustment::class)
            <a href="{{ route('stock-adjustments.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Adjustment
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Adjustment Number</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockAdjustments as $adjustment)
                        <tr>
                            <td><a href="{{ route('stock-adjustments.show', $adjustment) }}">{{ $adjustment->adjustment_number }}</a></td>
                            <td>{{ $adjustment->warehouse->name }}</td>
                            <td>{{ $adjustment->adjustment_date->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge {{ $adjustment->status === 'posted' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($adjustment->status) }}
                                </span>
                            </td>
                            <td>{{ $adjustment->createdBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No stock adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $stockAdjustments->links() }}
    </div>
@endsection
