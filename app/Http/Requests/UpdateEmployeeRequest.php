<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.edit');
    }

    /**
     * The linked user account (name/email/password/roles) is managed on the
     * Users or Riders screen, not here — this only touches HR/payroll data.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'employee_code')->ignore($this->route('employee')->id),
            ],
            'designation' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract'],
            'joining_date' => ['nullable', 'date'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'employment_status' => ['required', 'in:active,inactive,on_leave,terminated'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
