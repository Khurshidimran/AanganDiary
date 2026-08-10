@extends('layouts.app')

@section('title', 'New Vendor')

@section('content')
    <h1 class="h4 mb-3">New Vendor</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('vendors.store') }}">
                @csrf
                @include('vendors._form', ['vendor' => null])
                <button type="submit" class="btn btn-primary">Create Vendor</button>
                <a href="{{ route('vendors.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
