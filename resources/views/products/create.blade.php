@extends('layouts.app')

@section('title', 'New Product')

@section('content')
    <h1 class="h4 mb-3">New Product</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                @include('products._form', ['product' => null, 'categories' => $categories, 'brands' => $brands, 'units' => $units])
                <button type="submit" class="btn btn-primary">Create Product</button>
                <a href="{{ route('products.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
