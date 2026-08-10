<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('brands.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
