@extends('layouts.app')

@section('title', 'Categories')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Categories</h1>
        @can('create', \App\Models\Category::class)
            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Category
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->parent?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $category->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($category->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $category)
                                    <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $category)
                                    <form action="{{ route('categories.destroy', $category) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this category?');">
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
                            <td colspan="4" class="text-center text-muted py-4">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $categories->links() }}
    </div>
@endsection
