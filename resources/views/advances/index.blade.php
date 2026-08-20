@extends('layouts.app')

@section('title', 'Advances')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Employee Advances</h1>
            <div class="text-muted small">Total outstanding: {{ number_format($totals['outstanding'], 2) }}</div>
        </div>
    </div>

    @can('employee_advances.create')
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Record New Advance</div>
            <div class="card-body">
                <form method="POST" action="{{ route('advances.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Employee</label>
                        <select name="employee_id" class="form-select form-select-sm @error('employee_id') is-invalid @enderror" required>
                            <option value="">Select employee</option>
                            @foreach ($employees as $id => $name)
                                <option value="{{ $id }}" @selected((string) old('employee_id') === (string) $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control form-control-sm @error('amount') is-invalid @enderror" required>
                        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Date Given</label>
                        <input type="date" name="date_given" value="{{ old('date_given', now()->toDateString()) }}" class="form-control form-control-sm @error('date_given') is-invalid @enderror" required>
                        @error('date_given') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Reason</label>
                        <input type="text" name="reason" value="{{ old('reason') }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Record Advance</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('advances.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-0">Employee</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">All employees</option>
                        @foreach ($employees as $id => $name)
                            <option value="{{ $id }}" @selected((string) request('employee_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="settled" @selected(request('status') === 'settled')>Settled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @if (request('employee_id') || request('status'))
                    <div class="col-md-2">
                        <a href="{{ route('advances.index') }}" class="btn btn-sm btn-link">Clear</a>
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
                        <th>Date Given</th>
                        <th>Employee</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Remaining</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Recorded By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($advances as $advance)
                        <tr>
                            <td>{{ $advance->date_given->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('employees.show', $advance->employee) }}">{{ $advance->employee->user->name }}</a>
                            </td>
                            <td class="text-end">{{ number_format($advance->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($advance->remaining_balance, 2) }}</td>
                            <td>{{ $advance->reason ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $advance->status === 'settled' ? 'bg-secondary' : 'bg-warning text-dark' }}">
                                    {{ str($advance->status)->headline() }}
                                </span>
                            </td>
                            <td>{{ $advance->recordedBy?->name ?? '—' }}</td>
                            <td class="text-end">
                                @can('employee_advances.edit')
                                    @if ($advance->status !== 'settled')
                                        <form method="POST" action="{{ route('advances.write-off', $advance) }}" class="d-inline" onsubmit="return confirm('Write off this advance without a payroll deduction?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Write Off</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No advances found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $advances->links() }}
    </div>
@endsection
