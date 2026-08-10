@extends('layouts.app')

@section('title', 'Edit Role')

@section('content')
    <h1 class="h4 mb-3">Edit Role</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.update', $role) }}">
                @csrf
                @method('PUT')
                @include('roles._form', ['permissions' => $permissions, 'role' => $role])
                <button type="submit" class="btn btn-primary">Update Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
