@extends('layouts.app')

@section('title', 'New Employee')

@section('content')
    <h1 class="h4 mb-3">New Employee</h1>

    <div class="card shadow-sm" style="max-width: 820px;">
        <div class="card-body">
            <form method="POST" action="{{ route('employees.store') }}">
                @csrf

                <h2 class="h6">User Account</h2>
                <p class="text-muted small">Link this employee to an existing account (e.g. a rider) or create a new one. Rider-specific operational data (vehicle, zone, wallet) stays on the Riders screen — this only adds HR/payroll data.</p>

                <div class="mb-3">
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="user_mode" id="mode_existing" value="existing"
                               {{ old('user_mode', 'existing') === 'existing' ? 'checked' : '' }} onclick="toggleUserMode()">
                        <label class="form-check-label" for="mode_existing">Link existing account</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="user_mode" id="mode_new" value="new"
                               {{ old('user_mode') === 'new' ? 'checked' : '' }} onclick="toggleUserMode()">
                        <label class="form-check-label" for="mode_new">Create new account</label>
                    </div>
                    @error('user_mode') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                <div id="existing_user_block" class="mb-3">
                    <label for="user_id" class="form-label">User</label>
                    <select id="user_id" name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                        <option value="">Select a user</option>
                        @foreach ($availableUsers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('user_id') === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if ($availableUsers->isEmpty())
                        <div class="form-text text-warning">No users are currently available to link (all existing users already have an employee profile).</div>
                    @endif
                </div>

                <div id="new_user_block" class="d-none">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone') }}"
                                   class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input id="password" type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roles (optional — grants system login access)</label>
                        <div>
                            @foreach ($roles as $role)
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="roles[]" id="role_{{ $role }}" value="{{ $role }}"
                                           {{ collect(old('roles'))->contains($role) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role }}">{{ $role }}</label>
                                </div>
                            @endforeach
                        </div>
                        @error('roles') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h2 class="h6">Employment Details</h2>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="employee_code" class="form-label">Employee Code</label>
                        <input id="employee_code" type="text" name="employee_code" value="{{ old('employee_code') }}"
                               class="form-control @error('employee_code') is-invalid @enderror" required>
                        @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="designation" class="form-label">Designation</label>
                        <input id="designation" type="text" name="designation" value="{{ old('designation') }}"
                               class="form-control @error('designation') is-invalid @enderror">
                        @error('designation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label">Department</label>
                        <input id="department" type="text" name="department" value="{{ old('department') }}"
                               class="form-control @error('department') is-invalid @enderror">
                        @error('department') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="employment_type" class="form-label">Employment Type</label>
                        <select id="employment_type" name="employment_type" class="form-select @error('employment_type') is-invalid @enderror">
                            @foreach (['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_type', 'full_time') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="joining_date" class="form-label">Joining Date</label>
                        <input id="joining_date" type="date" name="joining_date" value="{{ old('joining_date') }}"
                               class="form-control @error('joining_date') is-invalid @enderror">
                        @error('joining_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="employment_status" class="form-label">Status</label>
                        <select id="employment_status" name="employment_status" class="form-select @error('employment_status') is-invalid @enderror">
                            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave', 'terminated' => 'Terminated'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('employment_status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="basic_salary" class="form-label">Basic Salary</label>
                        <input id="basic_salary" type="number" step="0.01" min="0" name="basic_salary" value="{{ old('basic_salary', 0) }}"
                               class="form-control @error('basic_salary') is-invalid @enderror" required>
                        @error('basic_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="cnic" class="form-label">CNIC</label>
                        <input id="cnic" type="text" name="cnic" value="{{ old('cnic') }}"
                               class="form-control @error('cnic') is-invalid @enderror">
                        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                               class="form-control @error('date_of_birth') is-invalid @enderror">
                        @error('date_of_birth') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="address" class="form-label">Address</label>
                    <textarea id="address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address') }}</textarea>
                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                        <input id="emergency_contact_name" type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"
                               class="form-control @error('emergency_contact_name') is-invalid @enderror">
                        @error('emergency_contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="emergency_contact_phone" class="form-label">Emergency Contact Phone</label>
                        <input id="emergency_contact_phone" type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"
                               class="form-control @error('emergency_contact_phone') is-invalid @enderror">
                        @error('emergency_contact_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bank_name" class="form-label">Bank Name</label>
                        <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name') }}"
                               class="form-control @error('bank_name') is-invalid @enderror">
                        @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="bank_account_number" class="form-label">Bank Account Number</label>
                        <input id="bank_account_number" type="text" name="bank_account_number" value="{{ old('bank_account_number') }}"
                               class="form-control @error('bank_account_number') is-invalid @enderror">
                        @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>

    <script>
        function toggleUserMode() {
            const isNew = document.getElementById('mode_new').checked;
            document.getElementById('existing_user_block').classList.toggle('d-none', isNew);
            document.getElementById('new_user_block').classList.toggle('d-none', !isNew);
        }
        toggleUserMode();
    </script>
@endsection
