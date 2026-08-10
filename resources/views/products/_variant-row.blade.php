@php
    $v = $variant ?? [];
    $get = fn ($key, $default = '') => old("variants.{$index}.{$key}", $v[$key] ?? $default);
    $selectedUnitId = (string) $get('unit_id');
@endphp

<div class="variant-row border rounded p-3 mb-2">
    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $v['id'] ?? '' }}">

    <div class="row g-2">
        <div class="col-md-5">
            <label class="form-label small">Variant Name</label>
            <input type="text" name="variants[{{ $index }}][name]" value="{{ $get('name') }}"
                   class="form-control form-control-sm variant-name-input" placeholder="e.g. 1 Liter" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">SKU</label>
            <input type="text" name="variants[{{ $index }}][sku]" value="{{ $get('sku') }}"
                   class="form-control form-control-sm" required>
        </div>
        <div class="col-md-4">
            <label class="form-label small">Barcode</label>
            <input type="text" name="variants[{{ $index }}][barcode]" value="{{ $get('barcode') }}"
                   class="form-control form-control-sm">
        </div>
    </div>

    <div class="row g-2 mt-1">
        <div class="col-md-2">
            <label class="form-label small">Pack Size</label>
            <input type="number" step="0.001" min="0" name="variants[{{ $index }}][pack_size]" value="{{ $get('pack_size') }}"
                   class="form-control form-control-sm variant-pack-size-input" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Unit</label>
            <select name="variants[{{ $index }}][unit_id]" class="form-select form-select-sm variant-unit-select" required>
                <option value="">Select</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" data-short-code="{{ $unit->short_code }}"
                            @selected($selectedUnitId === (string) $unit->id)>
                        {{ $unit->name }} ({{ $unit->short_code }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Purchase Price</label>
            <input type="number" step="0.01" min="0" name="variants[{{ $index }}][purchase_price]" value="{{ $get('purchase_price', 0) }}"
                   class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Sale Price</label>
            <input type="number" step="0.01" min="0" name="variants[{{ $index }}][sale_price]" value="{{ $get('sale_price', 0) }}"
                   class="form-control form-control-sm" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Compare-at Price</label>
            <input type="number" step="0.01" min="0" name="variants[{{ $index }}][compare_at_price]" value="{{ $get('compare_at_price') }}"
                   class="form-control form-control-sm">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-2">
        <div class="form-check">
            <input type="hidden" name="variants[{{ $index }}][is_active]" value="0">
            <input type="checkbox" name="variants[{{ $index }}][is_active]" value="1"
                   class="form-check-input" id="active-{{ $index }}" @checked($get('is_active', true))>
            <label class="form-check-label small" for="active-{{ $index }}">Active</label>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger remove-variant-row">
            <i class="bi bi-trash"></i> Remove
        </button>
    </div>
</div>
