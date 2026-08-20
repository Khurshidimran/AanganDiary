@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Employees</h1>
        @can('create', \App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Employee
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
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th class="text-end">Basic Salary</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->employee_code }}</td>
                            <td>
                                {{ $employee->user->name }}
                                @if ($employee->user->riderProfile)
                                    <span class="badge bg-info text-dark ms-1">Rider</span>
                                @endif
                            </td>
                            <td>{{ $employee->designation ?? '—' }}</td>
                            <td>{{ $employee->department ?? '—' }}</td>
                            <td>{{ str($employee->employment_type)->headline() }}</td>
                            <td class="text-end">{{ number_format($employee->basic_salary, 2) }}</td>
                            <td>
                                <span class="badge {{ match ($employee->employment_status) {
                                    'active' => 'bg-success',
                                    'on_leave' => 'bg-warning text-dark',
                                    'terminated' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">
                                    {{ str($employee->employment_status)->headline() }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('update', $employee)
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $employee)
                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this employee?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No employees found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $employees->links() }}
    </div>
@endsection
