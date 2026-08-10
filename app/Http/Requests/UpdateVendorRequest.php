<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('vendors.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'payment_terms' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', 'in:active,inactive'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
