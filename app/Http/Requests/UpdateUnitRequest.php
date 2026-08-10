<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('units.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($this->route('unit'))],
            'short_code' => ['required', 'string', 'max:10', Rule::unique('units', 'short_code')->ignore($this->route('unit'))],
            'type' => ['required', 'in:mass,volume,count'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
