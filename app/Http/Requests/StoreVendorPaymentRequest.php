<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVendorPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordPayment', $this->route('vendor'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
