<?php

namespace App\Http\Requests;

use App\Services\RiderSettlementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayRiderEarningsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $earningsPayable = app(RiderSettlementService::class)->financials($this->route('rider'))['earnings_payable'];

        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max($earningsPayable, 0)],
            'payment_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Payment amount cannot exceed the current Earnings Payable balance.',
        ];
    }
}
