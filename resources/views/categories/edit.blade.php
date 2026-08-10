@extends('layouts.app')

@section('title', 'Edit Category')

@section('content')
    <h1 class="h4 mb-3">Edit Category</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.update', $category) }}">
                @csrf
                @method('PUT')
                @include('categories._form', ['parents' => $parents, 'category' => $category])
                <button type="submit" class="btn btn-primary">Update Category</button>
                <a href="{{ route('categories.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
