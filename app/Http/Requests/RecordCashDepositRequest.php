<?php

namespace App\Http\Requests;

use App\Services\RiderSettlementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordCashDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Real gate is the rider_wallet.manage permission check in the
        // controller — this just governs field-level validation.
        return true;
    }

    public function rules(): array
    {
        $cashToHandIn = app(RiderSettlementService::class)->financials($this->route('rider'))['cash_to_hand_in'];

        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max($cashToHandIn, 0)],
            'deposit_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
            'reference_number' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.max' => 'Deposit amount cannot exceed the current Cash to Hand In balance.',
        ];
    }
}
