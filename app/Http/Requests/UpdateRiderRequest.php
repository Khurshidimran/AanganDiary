<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('riders.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('rider')->user_id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:20'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'vehicle_type' => ['required', 'in:bike,van,bicycle,car'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'zone' => ['nullable', 'string', 'max:255'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'per_delivery_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive,on_leave'],
        ];
    }
}
