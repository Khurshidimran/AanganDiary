<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('units.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'short_code' => ['required', 'string', 'max:10', 'unique:units,short_code'],
            'type' => ['required', 'in:mass,volume,count'],
            'conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
