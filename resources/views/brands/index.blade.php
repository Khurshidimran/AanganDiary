@extends('layouts.app')

@section('title', 'Brands')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Brands</h1>
        @can('create', \App\Models\Brand::class)
            <a href="{{ route('brands.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Brand
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td>{{ $brand->name }}</td>
                            <td>
                                <span class="badge {{ $brand->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($brand->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $brand)
                                    <a href="{{ route('brands.edit', $brand) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $brand)
                                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this brand?');">
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
                            <td colspan="3" class="text-center text-muted py-4">No brands found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $brands->links() }}
    </div>
@endsection
