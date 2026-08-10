@extends('layouts.app')

@section('title', 'Edit Stock Transfer')

@section('content')
    <h1 class="h4 mb-3">Edit Transfer {{ $stockTransfer->transfer_number }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('stock-transfers.update', $stockTransfer) }}">
                @csrf
                @method('PUT')
                @include('stock-transfers._form', ['stockTransfer' => $stockTransfer, 'warehouses' => $warehouses, 'variants' => $variants, 'stockByWarehouseJson' => $stockByWarehouseJson, 'lastPurchasePrices' => $lastPurchasePrices])
                <button type="submit" class="btn btn-primary">Update Transfer</button>
                <a href="{{ route('stock-transfers.show', $stockTransfer) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
