@php
    $it = $item ?? [];
    $get = fn ($key, $default = '') => old("items.{$index}.{$key}", $it[$key] ?? $default);
    $selectedVariantId = (string) $get('product_variant_id');
@endphp

<div class="item-row border rounded p-3 mb-2">
    <div class="row g-2">
        <div class="col-md-6">
            <label class="form-label small">Product Variant</label>
            <select name="items[{{ $index }}][product_variant_id]" class="form-select form-select-sm item-variant-select" required>
                <option value="">Select a variant</option>
                @foreach ($variants->groupBy('product.name') as $productName => $productVariants)
                    <optgroup label="{{ $productName }}">
                        @foreach ($productVariants as $variant)
                            <option value="{{ $variant->id }}" data-last-price="{{ $lastPurchasePrices[$variant->id] ?? '' }}"
                                    @selected($selectedVariantId === (string) $variant->id)>
                                {{ $variant->name }} ({{ $variant->sku }})
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Batch Number (optional)</label>
            <input type="text" name="items[{{ $index }}][batch_number]" value="{{ $get('batch_number') }}"
                   class="form-control form-control-sm">
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="button" class="btn btn-sm btn-outline-danger remove-item-row w-100">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
    </div>
    <div class="row g-2 mt-1">
        <div class="col-md-3">
            <label class="form-label small">Quantity</label>
            <input type="number" step="0.001" min="0.001" name="items[{{ $index }}][quantity]" value="{{ $get('quantity') }}"
                   class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">
                Unit Cost
                <i class="bi bi-info-circle text-muted item-price-hint" title="Prefilled from the last purchase price when known"></i>
            </label>
            <input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_cost]" value="{{ $get('unit_cost') }}"
                   class="form-control form-control-sm item-unit-cost-input" required>
        </div>
    </div>
</div>
