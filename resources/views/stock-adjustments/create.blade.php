@extends('layouts.app')

@section('title', 'New Stock Adjustment')

@section('content')
    <h1 class="h4 mb-3">New Stock Adjustment</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-adjustments.create') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="warehouse_id" class="form-label">Warehouse</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        <option value="">Select a warehouse</option>
                        @foreach ($warehouses as $id => $name)
                            <option value="{{ $id }}" @selected($selectedWarehouseId === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category (optional)</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected($selectedCategoryId === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">Load Products</button>
                </div>
            </form>
        </div>
    </div>

    @if ($selectedWarehouseId)
        <div class="card shadow-sm">
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('stock-adjustments.store') }}">
                    @csrf
                    <input type="hidden" name="warehouse_id" value="{{ $selectedWarehouseId }}">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="adjustment_date" class="form-label">Adjustment Date</label>
                            <input id="adjustment_date" type="date" name="adjustment_date" value="{{ old('adjustment_date', now()->toDateString()) }}"
                                   class="form-control" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <input id="notes" type="text" name="notes" value="{{ old('notes') }}" class="form-control">
                        </div>
                    </div>

                    @include('stock-adjustments._rows-table', ['rows' => $rows])

                    <button type="submit" class="btn btn-primary">Save as Draft</button>
                    <a href="{{ route('stock-adjustments.index') }}" class="btn btn-link">Cancel</a>
                </form>
            </div>
        </div>
    @endif
@endsection
