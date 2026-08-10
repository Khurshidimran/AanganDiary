@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Vendors</h1>
        @can('create', \App\Models\Vendor::class)
            <a href="{{ route('vendors.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Vendor
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td><a href="{{ route('vendors.show', $vendor) }}">{{ $vendor->name }}</a></td>
                            <td>{{ $vendor->company_name ?? '—' }}</td>
                            <td>{{ $vendor->contact_person ?? '—' }} {{ $vendor->phone ? "($vendor->phone)" : '' }}</td>
                            <td>
                                <span class="badge {{ $vendor->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($vendor->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $vendor)
                                    <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $vendor)
                                    <form action="{{ route('vendors.destroy', $vendor) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this vendor?');">
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
                            <td colspan="5" class="text-center text-muted py-4">No vendors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $vendors->links() }}
    </div>
@endsection
