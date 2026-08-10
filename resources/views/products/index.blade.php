@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Products</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('products.report') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-file-earmark-bar-graph"></i> Product Report
            </a>
            @can('create', \App\Models\Product::class)
                <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> New Product
                </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Variants</th>
                        <th>Supply Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? '—' }}</td>
                            <td>{{ $product->brand?->name ?? '—' }}</td>
                            <td>
                                @foreach ($product->variants as $variant)
                                    <span class="badge bg-secondary">
                                        {{ $variant->name }} ({{ $variant->sku }}) — {{ rtrim(rtrim($variant->pack_size, '0'), '.') }}{{ $variant->unit?->short_code }}
                                    </span>
                                @endforeach
                            </td>
                            <td>{{ ucfirst($product->supply_type) }}</td>
                            <td>
                                <span class="badge {{ $product->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $product)
                                    <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $product)
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this product?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $products->links() }}
    </div>
@endsection
