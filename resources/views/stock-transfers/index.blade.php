@extends('layouts.app')

@section('title', 'Stock Transfers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Stock Transfers</h1>
        @can('create', \App\Models\StockTransfer::class)
            <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Transfer
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Transfer Number</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockTransfers as $transfer)
                        <tr>
                            <td><a href="{{ route('stock-transfers.show', $transfer) }}">{{ $transfer->transfer_number }}</a></td>
                            <td>{{ $transfer->fromWarehouse->name }}</td>
                            <td>{{ $transfer->toWarehouse->name }}</td>
                            <td>{{ $transfer->transfer_date->format('Y-m-d') }}</td>
                            <td><span class="badge bg-secondary">{{ str($transfer->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No stock transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $stockTransfers->links() }}
    </div>
@endsection
