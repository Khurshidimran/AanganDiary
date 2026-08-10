@extends('layouts.app')

@section('title', 'New Role')

@section('content')
    <h1 class="h4 mb-3">New Role</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('roles.store') }}">
                @csrf
                @include('roles._form', ['permissions' => $permissions, 'role' => null])
                <button type="submit" class="btn btn-primary">Create Role</button>
                <a href="{{ route('roles.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
