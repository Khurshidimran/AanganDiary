@extends('layouts.app')

@section('title', 'Warehouses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Warehouses</h1>
        @can('create', \App\Models\Warehouse::class)
            <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Warehouse
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($warehouses as $warehouse)
                        <tr>
                            <td>{{ $warehouse->name }}</td>
                            <td>{{ $warehouse->code }}</td>
                            <td>{{ $warehouse->contact_person ?? '—' }} {{ $warehouse->phone ? "($warehouse->phone)" : '' }}</td>
                            <td>
                                <span class="badge {{ $warehouse->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($warehouse->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $warehouse)
                                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $warehouse)
                                    <form action="{{ route('warehouses.destroy', $warehouse) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this warehouse?');">
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
                            <td colspan="5" class="text-center text-muted py-4">No warehouses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $warehouses->links() }}
    </div>
@endsection
