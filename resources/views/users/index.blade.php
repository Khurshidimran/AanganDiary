@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Users</h1>
        @can('create', \App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New User
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach ($user->roles as $role)
                                    <span class="badge bg-secondary">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge {{ $user->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($user->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $user)
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $user)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
@endsection
