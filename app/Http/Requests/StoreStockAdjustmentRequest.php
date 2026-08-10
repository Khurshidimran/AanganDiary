<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock_adjustments.create');
    }

    /**
     * The bulk table submits one row per listed variant, most left blank.
     * Only rows where a quantity was actually entered become real items.
     */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => filled($item['quantity'] ?? null) && (float) $item['quantity'] > 0)
            ->map(fn ($item, $variantId) => [...$item, 'product_variant_id' => $variantId])
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.batch_number' => ['nullable', 'string', 'max:100'],
            'items.*.direction' => ['required', 'in:increase,decrease'],
            'items.*.reason' => ['required', 'in:stock_adjustment,wastage,damage'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Enter a quantity for at least one product to record an adjustment.',
            'items.min' => 'Enter a quantity for at least one product to record an adjustment.',
        ];
    }
}
