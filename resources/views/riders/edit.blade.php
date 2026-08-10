@extends('layouts.app')

@section('title', 'Edit Rider')

@section('content')
    <h1 class="h4 mb-3">Edit Rider</h1>

    <div class="card shadow-sm" style="max-width: 720px;">
        <div class="card-body">
            <form method="POST" action="{{ route('riders.update', $rider) }}">
                @csrf
                @method('PUT')
                @include('riders._form', ['rider' => $rider, 'warehouses' => $warehouses])
                <button type="submit" class="btn btn-primary">Update Rider</button>
                <a href="{{ route('riders.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
