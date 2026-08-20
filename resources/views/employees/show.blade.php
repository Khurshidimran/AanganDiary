@extends('layouts.app')

@section('title', 'Employee — ' . $employee->user->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $employee->user->name }}</h1>
            <div class="text-muted small">
                {{ $employee->employee_code }}
                @if ($employee->designation) &middot; {{ $employee->designation }} @endif
                @if ($employee->department) &middot; {{ $employee->department }} @endif
                @if ($employee->user->riderProfile)
                    <span class="badge bg-info text-dark ms-1">Rider</span>
                @endif
            </div>
        </div>
        @can('update', $employee)
            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Employment</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Type</dt>
                        <dd class="col-7">{{ str($employee->employment_type)->headline() }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">{{ str($employee->employment_status)->headline() }}</dd>
                        <dt class="col-5">Joining Date</dt>
                        <dd class="col-7">{{ $employee->joining_date?->format('Y-m-d') ?? '—' }}</dd>
                        <dt class="col-5">Basic Salary</dt>
                        <dd class="col-7">{{ number_format($employee->basic_salary, 2) }}</dd>
                        <dt class="col-5">Email</dt>
                        <dd class="col-7">{{ $employee->user->email }}</dd>
                        <dt class="col-5">Phone</dt>
                        <dd class="col-7">{{ $employee->user->phone ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Personal &amp; Banking</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">CNIC</dt>
                        <dd class="col-7">{{ $employee->cnic ?? '—' }}</dd>
                        <dt class="col-5">Date of Birth</dt>
                        <dd class="col-7">{{ $employee->date_of_birth?->format('Y-m-d') ?? '—' }}</dd>
                        <dt class="col-5">Emergency Contact</dt>
                        <dd class="col-7">{{ $employee->emergency_contact_name ?? '—' }} {{ $employee->emergency_contact_phone ? "({$employee->emergency_contact_phone})" : '' }}</dd>
                        <dt class="col-5">Bank</dt>
                        <dd class="col-7">{{ $employee->bank_name ?? '—' }} {{ $employee->bank_account_number ? "— {$employee->bank_account_number}" : '' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Advances</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date Given</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Remaining</th>
                        <th>Reason</th>
        <th>Status</th>
                        <th>Recorded By</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employee->advances as $advance)
                        <tr>
                            <td>{{ $advance->date_given->format('Y-m-d') }}</td>
                            <td class="text-end">{{ number_format($advance->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($advance->remaining_balance, 2) }}</td>
                            <td>{{ $advance->reason ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $advance->status === 'settled' ? 'bg-secondary' : 'bg-warning text-dark' }}">
                                    {{ str($advance->status)->headline() }}
                                </span>
                            </td>
                            <td>{{ $advance->recordedBy?->name ?? '—' }}</td>
                            <td>
                                @can('employee_advances.edit')
                                    @if ($advance->status !== 'settled')
                                        <form method="POST" action="{{ route('advances.write-off', $advance) }}" onsubmit="return confirm('Write off this advance without a payroll deduction?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">Write Off</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">No advances recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('employee_advances.create')
            <div class="card-body border-top">
                <form method="POST" action="{{ route('advances.store') }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Date Given</label>
                        <input type="date" name="date_given" value="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0">Reason</label>
                        <input type="text" name="reason" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Record Advance</button>
                    </div>
                </form>
            </div>
        @endcan
        <div class="card-body border-top py-2">
            <a href="{{ route('advances.index', ['employee_id' => $employee->id]) }}" class="small">View all advances for this employee &rarr;</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Recent Payroll History</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th class="text-end">Basic</th>
                        <th class="text-end">Delivery Earnings</th>
                        <th class="text-end">Deductions</th>
                        <th class="text-end">Net Pay</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payrollHistory as $item)
                        <tr>
                            <td>
                                <a href="{{ route('payroll.show', $item->payrollRun) }}">
                                    {{ $item->payrollRun->period_start->format('Y-m-d') }} to {{ $item->payrollRun->period_end->format('Y-m-d') }}
                                </a>
                            </td>
                            <td class="text-end">{{ number_format($item->basic_salary, 2) }}</td>
                            <td class="text-end">{{ number_format($item->delivery_earnings, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_deductions, 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($item->net_pay, 2) }}</td>
                            <td>{{ str($item->payrollRun->status)->headline() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">No payroll history yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
