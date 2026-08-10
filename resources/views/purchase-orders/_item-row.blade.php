@php
    $it = $item ?? [];
    $get = fn ($key, $default = '') => old("items.{$index}.{$key}", $it[$key] ?? $default);
    $selectedVariantId = (string) $get('product_variant_id');
@endphp

<div class="item-row border rounded p-3 mb-2">
    <div class="row g-2">
        <div class="col-md-5">
            <label class="form-label small">Product Variant</label>
            <select name="items[{{ $index }}][product_variant_id]" class="form-select form-select-sm item-variant-select" required>
                <option value="">Select a variant</option>
                @foreach ($variants->groupBy('product.name') as $productName => $productVariants)
                    <optgroup label="{{ $productName }}">
                        @foreach ($productVariants as $variant)
                            <option value="{{ $variant->id }}" data-purchase-price="{{ $variant->purchase_price }}"
                                    @selected($selectedVariantId === (string) $variant->id)>
                                {{ $variant->name }} ({{ $variant->sku }})
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Quantity</label>
            <input type="number" step="0.001" min="0.001" name="items[{{ $index }}][quantity_ordered]" value="{{ $get('quantity_ordered') }}"
                   class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Unit Cost</label>
            <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" value="{{ $get('unit_cost') }}"
                   class="form-control form-control-sm item-unit-cost-input" required>
        </div>
        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-item-row">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>
