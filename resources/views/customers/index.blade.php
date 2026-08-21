@extends('layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Customers</h1>
        @can('create', \App\Models\Customer::class)
            <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Customer
            </a>
        @endcan
    </div>

    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width: 360px;">
            <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Search name, phone, or email">
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Linked Account</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->email ?? '—' }}</td>
                            <td>
                                @if ($customer->account_id)
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">Mapped</span>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $customer->orders_count }}</td>
                            <td class="text-end">
                                @can('update', $customer)
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $customer)
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this customer?');">
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
                            <td colspan="6" class="text-center text-muted py-4">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $customers->links() }}
    </div>
@endsection
