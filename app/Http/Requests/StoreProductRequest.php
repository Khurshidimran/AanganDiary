<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'tax_rate' => ['nullable', 'numeric', 'between:0,100'],
            'is_bundle' => ['boolean'],
            'supply_type' => ['required', 'in:purchased,manufactured,both'],
            'track_inventory' => ['boolean'],
            'track_batch' => ['boolean'],
            'track_expiry' => ['boolean'],
            'status' => ['required', 'in:active,inactive'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct', 'unique:product_variants,sku'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100', 'distinct', 'unique:product_variants,barcode'],
            'variants.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.unit_id' => ['required', 'exists:units,id'],
            'variants.*.pack_size' => ['required', 'numeric', 'min:0.001'],
            'variants.*.is_active' => ['boolean'],

            'bundle_items' => ['required_if:is_bundle,1', 'array'],
            'bundle_items.*.component_variant_id' => ['required_with:bundle_items', 'exists:product_variants,id'],
            'bundle_items.*.quantity' => ['required_with:bundle_items', 'numeric', 'min:0.001'],
        ];
    }
}
