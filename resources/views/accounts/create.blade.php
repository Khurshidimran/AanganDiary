@extends('layouts.app')

@section('title', 'New Account')

@section('content')
    <h1 class="h4 mb-3">New Account</h1>

    <div class="card shadow-sm" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST" action="{{ route('accounts.store') }}">
                @csrf
                @include('accounts._form', ['account' => null, 'parents' => $parents])
                <button type="submit" class="btn btn-primary">Create Account</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
