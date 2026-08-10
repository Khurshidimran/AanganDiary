@extends('layouts.app')

@section('title', 'New Brand')

@section('content')
    <h1 class="h4 mb-3">New Brand</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('brands.store') }}">
                @csrf
                @include('brands._form', ['brand' => null])
                <button type="submit" class="btn btn-primary">Create Brand</button>
                <a href="{{ route('brands.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
