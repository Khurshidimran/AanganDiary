@extends('layouts.app')

@section('title', 'Edit Employee')

@section('content')
    <h1 class="h4 mb-3">Edit Employee — {{ $employee->user->name }}</h1>

    <div class="card shadow-sm" style="max-width: 820px;">
        <div class="card-body">
            <form method="POST" action="{{ route('employees.update', $employee) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="employee_code" class="form-label">Employee Code</label>
                        <input id="employee_code" type="text" name="employee_code" value="{{ old('employee_code', $employee->employee_code) }}"
                               class="form-control @error('employee_code') is-invalid @enderror" required>
                        @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="designation" class="form-label">Designation</label>
                        <input id="designation" type="text" name="designation" value="{{ old('designation', $employee->designation) }}"
                               class="form-control @error('designation') is-invalid @enderror">
                        @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input id="department" type="text" name="department" value="{{ old('department', $employee->department) }}"
                               class="form-control @error('department') is-invalid @enderror">
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="employment_type" class="form-label">Employment Type</label>
                        <select id="employment_type" name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                            @foreach (['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', $employee->employment_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="joining_date" class="form-label">Joining Date</label>
                        <input id="joining_date" type="date" name="joining_date" value="{{ old('joining_date', $employee->joining_date?->toDateString()) }}"
                               class="form-control @error('joining_date') is-invalid @enderror">
                        @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="employment_status" class="form-label">Status</label>
                        <select id="employment_status" name="employment_status" class="form-select @error('employment_status') is-invalid @enderror">
                            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave', 'terminated' => 'Terminated'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_status', $employee->employment_status) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="basic_salary" class="form-label">Basic Salary</label>
                        <input id="basic_salary" type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', $employee->basic_salary) }}"
                               class="form-control @error('basic_salary') is-invalid @enderror" required>
                        @error('basic_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cnic" class="form-label">CNIC</label>
                        <input id="cnic" type="text" name="cnic" value="{{ old('cnic', $employee->cnic) }}"
                               class="form-control @error('cnic') is-invalid @enderror">
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->toDateString()) }}"
                               class="form-control @error('date_of_birth') is-invalid @enderror">
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $employee->address) }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                        <input id="emergency_contact_name" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name) }}"
                               class="form-control @error('emergency_contact_name') is-invalid @enderror">
                        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                        <input id="emergency_contact_phone" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone) }}"
                               class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                        @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name', $employee->bank_name) }}"
                               class="form-control @error('bank_name') is-invalid @enderror">
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="bank_account_number" class="form-label">Bank Account Number</label>
                        <input id="bank_account_number" type="text" name="bank_account_number" value="{{ old('bank_account_number', $employee->bank_account_number) }}"
                               class="form-control @error('bank_account_number') is-invalid @enderror">
                        @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $employee->notes) }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Update Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>
@endsection
