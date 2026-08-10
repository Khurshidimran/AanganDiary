@extends('layouts.app')

@section('title', 'New Purchase Order')

@section('content')
    <h1 class="h4 mb-3">New Purchase Order</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('purchase-orders.store') }}">
                @csrf
                @include('purchase-orders._form', ['purchaseOrder' => null, 'vendors' => $vendors, 'warehouses' => $warehouses, 'variants' => $variants])
                <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                <a href="{{ route('purchase-orders.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
