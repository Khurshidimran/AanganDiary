@extends('layouts.app')

@section('title', 'Edit Stock Adjustment')

@section('content')
    <h1 class="h4 mb-3">Edit Draft {{ $stockAdjustment->adjustment_number }}</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-adjustments.edit', $stockAdjustment) }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Warehouse</label>
                    <input type="text" class="form-control" value="{{ $stockAdjustment->warehouse->name }}" disabled>
                    <div class="form-text">Warehouse can't change after the draft is created.</div>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Filter by Category (optional)</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected($selectedCategoryId === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

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

            <form method="POST" action="{{ route('stock-adjustments.update', $stockAdjustment) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="adjustment_date" class="form-label">Adjustment Date</label>
                        <input id="adjustment_date" type="date" name="adjustment_date"
                               value="{{ old('adjustment_date', $stockAdjustment->adjustment_date->toDateString()) }}"
                               class="form-control" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <input id="notes" type="text" name="notes" value="{{ old('notes', $stockAdjustment->notes) }}" class="form-control">
                    </div>
                </div>

                @include('stock-adjustments._rows-table', ['rows' => $rows])

                <button type="submit" class="btn btn-primary">Update Draft</button>
                <a href="{{ route('stock-adjustments.show', $stockAdjustment) }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
