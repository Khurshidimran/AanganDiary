<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_mode' => ['required', 'in:existing,new'],
            'user_id' => ['required_if:user_mode,existing', 'nullable', 'exists:users,id', 'unique:employees,user_id'],
            'name' => ['required_if:user_mode,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:user_mode,new', 'nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required_if:user_mode,new', 'nullable', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,name'],

            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
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
