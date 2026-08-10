@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
    <h1 class="h4 mb-3">Edit User</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                @include('users._form', ['roles' => $roles, 'user' => $user])
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="{{ route('users.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
