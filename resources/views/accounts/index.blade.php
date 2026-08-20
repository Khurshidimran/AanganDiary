@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Chart of Accounts</h1>
        @can('create', \App\Models\Account::class)
            <a href="{{ route('accounts.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Account
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>{{ $account->code }}</td>
                            <td>
                                {{ $account->parent ? '— ' : '' }}{{ $account->name }}
                                @if ($account->is_system)
                                    <span class="badge bg-secondary ms-1" title="System-protected control account">System</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ str($account->type)->headline() }}</span></td>
                            <td>{{ $account->parent?->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $account->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ str($account->status)->headline() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('reports.ledger', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-secondary" title="View ledger">
                                    <i class="bi bi-journal-text"></i>
                                </a>
                                @can('update', $account)
                                    <a href="{{ route('accounts.edit', $account) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $account)
                                    @if (! $account->is_system)
                                        <form action="{{ route('accounts.destroy', $account) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
