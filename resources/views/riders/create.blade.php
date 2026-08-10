@extends('layouts.app')

@section('title', 'New Rider')

@section('content')
    <h1 class="h4 mb-3">New Rider</h1>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('riders.store') }}">
                @csrf
                @include('riders._form', ['rider' => null, 'warehouses' => $warehouses])
                <button type="submit" class="btn btn-primary">Create Rider</button>
                <a href="{{ route('riders.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
