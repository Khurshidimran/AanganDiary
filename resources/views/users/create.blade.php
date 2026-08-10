@extends('layouts.app')

@section('title', 'New User')

@section('content')
    <h1 class="h4 mb-3">New User</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form', ['roles' => $roles, 'user' => null])
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="{{ route('users.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
