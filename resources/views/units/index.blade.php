@extends('layouts.app')

@section('title', 'Units of Measure')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Units of Measure</h1>
        @can('create', \App\Models\Unit::class)
            <a href="{{ route('units.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Unit
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Short Code</th>
                        <th>Type</th>
                        <th>Conversion Factor</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr>
                            <td>{{ $unit->name }}</td>
                            <td>{{ $unit->short_code }}</td>
                            <td>{{ ucfirst($unit->type) }}</td>
                            <td>{{ $unit->conversion_factor }}</td>
                            <td>
                                <span class="badge {{ $unit->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($unit->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $unit)
                                    <a href="{{ route('units.edit', $unit) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $unit)
                                    <form action="{{ route('units.destroy', $unit) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this unit?');">
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
                            <td colspan="6" class="text-center text-muted py-4">No units found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $units->links() }}
    </div>
@endsection
