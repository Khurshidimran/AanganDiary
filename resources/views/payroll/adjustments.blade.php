@extends('layouts.app')

@section('title', 'Deductions & Additions History')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Deductions &amp; Additions History</h1>
        <a href="{{ route('payroll.index') }}" class="btn btn-link btn-sm">Back to Payroll Runs</a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('payroll.adjustments.index') }}" class="row g-2 align-items-end">
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
                    <label class="form-label small mb-0">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        <option value="addition" @selected(request('type') === 'addition')>Addition</option>
                        <option value="deduction" @selected(request('type') === 'deduction')>Deduction</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @if (request('employee_id') || request('type'))
                    <div class="col-md-2">
                        <a href="{{ route('payroll.adjustments.index') }}" class="btn btn-sm btn-link">Clear</a>
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
                        <th>Employee</th>
                        <th>Payroll Period</th>
                        <th>Type</th>
                        <th>Label</th>
                        <th class="text-end">Amount</th>
                        <th>Linked Advance</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adjustments as $adjustment)
                        <tr>
                            <td>{{ $adjustment->created_at->format('Y-m-d') }}</td>
                            <td>
                                <a href="{{ route('employees.show', $adjustment->payrollRunItem->employee) }}">
                                    {{ $adjustment->payrollRunItem->employee->user->name }}
                                </a>
                            </td>
                            <td>
                                <a href="{{ route('payroll.show', $adjustment->payrollRunItem->payrollRun) }}">
                                    {{ $adjustment->payrollRunItem->payrollRun->period_start->format('Y-m-d') }} to {{ $adjustment->payrollRunItem->payrollRun->period_end->format('Y-m-d') }}
                                </a>
                            </td>
                            <td>
                                <span class="badge {{ $adjustment->type === 'addition' ? 'bg-success' : 'bg-danger' }}">
                                    {{ str($adjustment->type)->headline() }}
                                </span>
                            </td>
                            <td>{{ $adjustment->label }}</td>
                            <td class="text-end">{{ number_format($adjustment->amount, 2) }}</td>
                            <td>{{ $adjustment->employeeAdvance ? 'Advance ('.number_format($adjustment->employeeAdvance->remaining_balance, 2).' left)' : '—' }}</td>
                            <td>{{ $adjustment->recordedBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No adjustments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $adjustments->links() }}
    </div>
@endsection
