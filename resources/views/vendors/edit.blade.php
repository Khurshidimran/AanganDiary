@extends('layouts.app')

@section('title', 'Edit Vendor')

@section('content')
    <h1 class="h4 mb-3">Edit Vendor</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('vendors.update', $vendor) }}">
                @csrf
                @method('PUT')
                @include('vendors._form', ['vendor' => $vendor])
                <button type="submit" class="btn btn-primary">Update Vendor</button>
                <a href="{{ route('vendors.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
