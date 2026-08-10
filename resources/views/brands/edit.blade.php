@extends('layouts.app')

@section('title', 'Edit Brand')

@section('content')
    <h1 class="h4 mb-3">Edit Brand</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('brands.update', $brand) }}">
                @csrf
                @method('PUT')
                @include('brands._form', ['brand' => $brand])
                <button type="submit" class="btn btn-primary">Update Brand</button>
                <a href="{{ route('brands.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
