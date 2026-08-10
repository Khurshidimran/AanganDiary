@php
    $b = $item ?? [];
    $get = fn ($key, $default = '') => old("bundle_items.{$index}.{$key}", $b[$key] ?? $default);
    $selectedVariantId = (string) $get('component_variant_id');
@endphp

<div class="row g-2 mb-2 bundle-item-row align-items-end">
    <div class="col-md-7">
        <label class="form-label small">Component Variant</label>
        <select name="bundle_items[{{ $index }}][component_variant_id]" class="form-select form-select-sm" required>
            <option value="">Select a variant</option>
            @foreach ($componentOptions as $productName => $variantsForProduct)
                <optgroup label="{{ $productName }}">
                    @foreach ($variantsForProduct as $variant)
                        <option value="{{ $variant->id }}" @selected($selectedVariantId === (string) $variant->id)>
                            {{ $variant->name }} ({{ $variant->sku }})
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Quantity per Bundle</label>
        <input type="number" step="0.001" min="0.001" name="bundle_items[{{ $index }}][quantity]" value="{{ $get('quantity', 1) }}"
               class="form-control form-control-sm" required>
    </div>
    <div class="col-md-2">
        <button type="button" class="btn btn-sm btn-outline-danger remove-bundle-item-row w-100">
            <i class="bi bi-trash"></i>
        </button>
    </div>
</div>
