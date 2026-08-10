@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <h1 class="h4 mb-3">Edit Product</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('products.update', $product) }}">
                @csrf
                @method('PUT')
                @include('products._form', ['product' => $product, 'categories' => $categories, 'brands' => $brands, 'units' => $units])
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="{{ route('products.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
