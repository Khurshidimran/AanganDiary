<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock_transfers.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
