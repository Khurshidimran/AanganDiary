@extends('layouts.app')

@section('title', 'Stock Balances')

@section('content')
    <h1 class="h4 mb-3">Stock Balances</h1>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="warehouse_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Warehouses</option>
                @foreach ($warehouses as $id => $name)
                    <option value="{{ $id }}" @selected(request('warehouse_id') === $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search product, variant or SKU">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Warehouse</th>
                        <th>Product</th>
                        <th>Variant</th>
                        <th>Batch</th>
                        <th class="text-end">Quantity</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balances as $balance)
                        <tr>
                            <td>{{ $balance->warehouse->name }}</td>
                            <td>{{ $balance->productVariant->product->name }}</td>
                            <td>{{ $balance->productVariant->name }} ({{ $balance->productVariant->sku }})</td>
                            <td>{{ $balance->batch_number ?: '—' }}</td>
                            <td class="text-end">{{ rtrim(rtrim($balance->quantity, '0'), '.') }} {{ $balance->productVariant->unit->short_code }}</td>
                            <td>
                                @if ($balance->expiry_date)
                                    <span class="{{ $balance->expiry_date->isPast() ? 'text-danger fw-semibold' : '' }}">
                                        {{ $balance->expiry_date->format('Y-m-d') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No stock on hand.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $balances->links() }}
    </div>
@endsection
