@extends('layouts.app')

@section('title', 'New Category')

@section('content')
    <h1 class="h4 mb-3">New Category</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form', ['parents' => $parents, 'category' => null])
                <button type="submit" class="btn btn-primary">Create Category</button>
                <a href="{{ route('categories.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
