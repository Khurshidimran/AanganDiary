@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Roles</h1>
        @can('create', \App\Models\Role::class)
            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Role
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>
                                @forelse ($role->permissions as $permission)
                                    <span class="badge bg-secondary">{{ $permission->name }}</span>
                                @empty
                                    <span class="text-muted small">None</span>
                                @endforelse
                            </td>
                            <td class="text-end">
                                @can('update', $role)
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $role)
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this role?');">
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
                            <td colspan="3" class="text-center text-muted py-4">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $roles->links() }}
    </div>
@endsection
