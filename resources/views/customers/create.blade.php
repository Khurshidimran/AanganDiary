@extends('layouts.app')

@section('title', 'New Customer')

@section('content')
    <h1 class="h4 mb-3">New Customer</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                @include('customers._form', ['customer' => null])
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <a href="{{ route('customers.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
