@extends('layouts.app')

@section('title', 'Edit Warehouse')

@section('content')
    <h1 class="h4 mb-3">Edit Warehouse</h1>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
                @csrf
                @method('PUT')
                @include('warehouses._form', ['warehouse' => $warehouse])
                <button type="submit" class="btn btn-primary">Update Warehouse</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
