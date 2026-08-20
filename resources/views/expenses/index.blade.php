@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Expenses</h1>
            <div class="text-muted small">Total (filtered): {{ number_format($total, 2) }}</div>
        </div>
        @can('create', \App\Models\Expense::class)
            <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Expense
            </a>
        @endcan
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('expenses.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">Category</label>
                    <select name="expense_category_id" class="form-select form-select-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('expense_category_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Warehouse</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All warehouses</option>
                        @foreach ($warehouses as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('warehouse_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @if (request()->anyFilled(['expense_category_id', 'warehouse_id', 'date_from', 'date_to']))
                    <div class="col-md-1">
                        <a href="{{ route('expenses.index') }}" class="btn btn-sm btn-link">Clear</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Warehouse</th>
                        <th class="text-end">Amount</th>
                        <th>Payment Method</th>
                        <th>Reference</th>
                        <th>Recorded By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date->format('Y-m-d') }}</td>
                            <td>{{ $expense->category->name }}</td>
                            <td>{{ $expense->warehouse?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                            <td><span class="badge bg-secondary">{{ str($expense->payment_method)->headline() }}</span></td>
                            <td>{{ $expense->reference_number ?? '—' }}</td>
                            <td>{{ $expense->recordedBy?->name ?? '—' }}</td>
                            <td class="text-end">
                                @can('update', $expense)
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $expense)
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this expense?');">
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
                            <td colspan="8" class="text-center text-muted py-4">No expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $expenses->links() }}
    </div>
@endsection
