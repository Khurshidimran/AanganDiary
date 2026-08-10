@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
    <h1 class="h4 mb-3">Edit Purchase Order {{ $purchaseOrder->po_number }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-orders.update', $purchaseOrder) }}">
                @csrf
                @method('PUT')
                @include('purchase-orders._form', ['purchaseOrder' => $purchaseOrder, 'vendors' => $vendors, 'warehouses' => $warehouses, 'variants' => $variants])
                <button type="submit" class="btn btn-primary">Update Purchase Order</button>
                <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
