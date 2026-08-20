<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('expense_categories.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('expense_categories', 'name')->ignore($this->route('expense_category')->id),
            ],
            'status' => ['required', 'in:active,inactive'],
            'chart_account_id' => ['nullable', 'exists:accounts,id'],
        ];
    }
}
