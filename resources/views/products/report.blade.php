@extends('layouts.app')

@section('title', 'Product Report')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Product Report</h1>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-seam"></i> Back to Products
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('products.report') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected($filters['category_id'] == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control"
                           placeholder="Product name, variant name, SKU, or barcode">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('products.report') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Variant</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th>Pack Size</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Sale Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($variants as $variant)
                        <tr>
                            <td>{{ $variant->product->name }}</td>
                            <td>{{ $variant->product->category?->name ?? '—' }}</td>
                            <td>{{ $variant->product->brand?->name ?? '—' }}</td>
                            <td>{{ $variant->name }}</td>
                            <td>{{ $variant->sku ?: '—' }}</td>
                            <td>{{ $variant->barcode ?: '—' }}</td>
                            <td>{{ rtrim(rtrim($variant->pack_size, '0'), '.') }} {{ $variant->unit?->short_code }}</td>
                            <td class="text-end">{{ number_format($variant->purchase_price, 2) }}</td>
                            <td class="text-end">{{ number_format($variant->sale_price, 2) }}</td>
                            <td>
                                <span class="badge {{ $variant->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $variant->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No variants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $variants->links() }}
    </div>
@endsection
