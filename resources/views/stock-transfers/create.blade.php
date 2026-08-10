@extends('layouts.app')

@section('title', 'New Stock Transfer')

@section('content')
    <h1 class="h4 mb-3">New Stock Transfer</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('stock-transfers.store') }}">
                @csrf
                @include('stock-transfers._form', ['stockTransfer' => null, 'warehouses' => $warehouses, 'variants' => $variants, 'stockByWarehouseJson' => $stockByWarehouseJson, 'lastPurchasePrices' => $lastPurchasePrices])
                <button type="submit" class="btn btn-primary">Create Transfer</button>
                <a href="{{ route('stock-transfers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
