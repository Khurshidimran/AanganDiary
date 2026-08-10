@extends('layouts.app')

@section('title', 'New Warehouse')

@section('content')
    <h1 class="h4 mb-3">New Warehouse</h1>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('warehouses.store') }}">
                @csrf
                @include('warehouses._form', ['warehouse' => null])
                <button type="submit" class="btn btn-primary">Create Warehouse</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
