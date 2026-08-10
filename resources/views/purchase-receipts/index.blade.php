@extends('layouts.app')

@section('title', 'Goods Receipts')

@section('content')
    <h1 class="h4 mb-3">Goods Receipts</h1>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Receipt Number</th>
                        <th>Purchase Order</th>
                        <th>Vendor</th>
                        <th>Warehouse</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseReceipts as $receipt)
                        <tr>
                            <td><a href="{{ route('purchase-receipts.show', $receipt) }}">{{ $receipt->receipt_number }}</a></td>
                            <td><a href="{{ route('purchase-orders.show', $receipt->purchaseOrder) }}">{{ $receipt->purchaseOrder->po_number }}</a></td>
                            <td>{{ $receipt->vendor->name }}</td>
                            <td>{{ $receipt->warehouse->name }}</td>
                            <td>{{ $receipt->receipt_date->format('Y-m-d') }}</td>
                            <td class="text-end">{{ number_format($receipt->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No goods receipts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $purchaseReceipts->links() }}
    </div>
@endsection
