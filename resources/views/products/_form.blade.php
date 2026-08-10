@php
    $existingVariants = old('variants', $product?->variants->map->only([
        'id', 'name', 'sku', 'barcode', 'purchase_price', 'sale_price', 'compare_at_price', 'unit_id', 'pack_size', 'is_active',
    ])->all() ?? []);
    $existingBundleItems = old('bundle_items', $product?->bundleItems->map->only([
        'component_variant_id', 'quantity',
    ])->all() ?? []);
    $isBundleValue = old('is_bundle', $product?->is_bundle ?? false);
@endphp

@if ($product && $product->images->isNotEmpty())
    <div class="mb-3">
        <label class="form-label">Synced Images</label>
        <div class="d-flex gap-2 flex-wrap">
            @foreach ($product->images as $image)
                <img src="{{ $image->url }}" alt="" style="width:80px;height:80px;object-fit:cover;" class="border rounded">
            @endforeach
        </div>
        <div class="form-text">Images are populated from Shopify sync — there's no manual upload yet.</div>
    </div>
@endif

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Product Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $product?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="category_id" class="form-label">Category</label>
        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($categories as $id => $name)
                <option value="{{ $id }}" @selected((string) old('category_id', $product?->category_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="brand_id" class="form-label">Brand</label>
        <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($brands as $id => $name)
                <option value="{{ $id }}" @selected((string) old('brand_id', $product?->brand_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product?->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="tags" class="form-label">Tags</label>
    <input id="tags" type="text" name="tags" value="{{ old('tags', $product?->tags) }}"
           class="form-control @error('tags') is-invalid @enderror" placeholder="e.g. cheese for pizza, cheddar cheese">
    @error('tags') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Comma-separated, used for search/merchandising.</div>
</div>

<div class="row">
    <div class="col-md-3 mb-3">
        <label for="unit_id" class="form-label">Default Unit</label>
        <select id="unit_id" name="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product?->unit_id) === (string) $unit->id)>
                    {{ $unit->name }} ({{ $unit->short_code }})
                </option>
            @endforeach
        </select>
        @error('unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Informational default for this product family — each variant sets its own pack size below.</div>
    </div>

    <div class="col-md-3 mb-3">
        <label for="tax_rate" class="form-label">Tax Rate (%)</label>
        <input id="tax_rate" type="number" step="0.01" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $product?->tax_rate) }}"
               class="form-control @error('tax_rate') is-invalid @enderror">
        @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="supply_type" class="form-label">Supply Type</label>
        <select id="supply_type" name="supply_type" class="form-select @error('supply_type') is-invalid @enderror">
            @foreach (['purchased' => 'Purchased', 'manufactured' => 'Manufactured', 'both' => 'Both'] as $value => $label)
                <option value="{{ $value }}" @selected(old('supply_type', $product?->supply_type ?? 'purchased') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('supply_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3 mb-3">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (['active', 'inactive'] as $value)
                <option value="{{ $value }}" @selected(old('status', $product?->status ?? 'active') === $value)>{{ ucfirst($value) }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="d-flex gap-4 mb-4">
    <div class="form-check">
        <input type="hidden" name="track_inventory" value="0">
        <input type="checkbox" name="track_inventory" value="1" class="form-check-input" id="track_inventory"
               @checked(old('track_inventory', $product?->track_inventory ?? true))>
        <label class="form-check-label" for="track_inventory">Track Inventory</label>
    </div>
    <div class="form-check">
        <input type="hidden" name="track_batch" value="0">
        <input type="checkbox" name="track_batch" value="1" class="form-check-input" id="track_batch"
               @checked(old('track_batch', $product?->track_batch ?? false))>
        <label class="form-check-label" for="track_batch">Track Batch</label>
    </div>
    <div class="form-check">
        <input type="hidden" name="track_expiry" value="0">
        <input type="checkbox" name="track_expiry" value="1" class="form-check-input" id="track_expiry"
               @checked(old('track_expiry', $product?->track_expiry ?? false))>
        <label class="form-check-label" for="track_expiry">Track Expiry</label>
    </div>
</div>

<hr>

<div class="form-check form-switch mb-3">
    <input type="hidden" name="is_bundle" value="0">
    <input type="checkbox" name="is_bundle" value="1" class="form-check-input" id="is_bundle" role="switch"
           @checked($isBundleValue)>
    <label class="form-check-label" for="is_bundle">This is a bundle/kit product</label>
    <div class="form-text">Its own stock isn't tracked directly — confirming an order for it deducts stock from the component variants below instead.</div>
</div>

<div id="bundle-items-section" class="{{ $isBundleValue ? '' : 'd-none' }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 mb-0">Bundle Components</h2>
        <button type="button" id="add-bundle-item-row" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus-lg"></i> Add Component
        </button>
    </div>
    @error('bundle_items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

    <div id="bundle-items-container">
        @foreach ($existingBundleItems as $index => $item)
            @include('products._bundle-item-row', ['index' => $index, 'item' => $item, 'componentOptions' => $componentOptions])
        @endforeach
    </div>

    <template id="bundle-item-row-template">
        @include('products._bundle-item-row', ['index' => '__INDEX__', 'item' => null, 'componentOptions' => $componentOptions])
    </template>
</div>

<hr>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">Variants</h2>
    <button type="button" id="add-variant-row" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-plus-lg"></i> Add Variant
    </button>
</div>
@error('variants') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

<div id="variants-container">
    @forelse ($existingVariants as $index => $variant)
        @include('products._variant-row', ['index' => $index, 'variant' => $variant])
    @empty
        @include('products._variant-row', ['index' => 0, 'variant' => null])
    @endforelse
</div>

<template id="variant-row-template">
    @include('products._variant-row', ['index' => '__INDEX__', 'variant' => null])
</template>

@push('scripts')
<script>
    (function () {
        let counter = {{ max((int) collect(array_keys($existingVariants))->max(), 0) + 1 }};
        const container = document.getElementById('variants-container');
        const template = document.getElementById('variant-row-template');

        document.getElementById('add-variant-row').addEventListener('click', function () {
            const html = template.innerHTML.replaceAll('__INDEX__', counter++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
        });

        container.addEventListener('click', function (event) {
            const button = event.target.closest('.remove-variant-row');
            if (!button) return;

            const rows = container.querySelectorAll('.variant-row');
            if (rows.length <= 1) {
                alert('A product must have at least one variant.');
                return;
            }

            button.closest('.variant-row').remove();
        });

        // Auto-suggest the variant name from pack size + unit (e.g. "250 g"),
        // but only while the user hasn't typed a name themselves.
        container.addEventListener('input', function (event) {
            if (event.target.classList.contains('variant-name-input')) {
                event.target.dataset.touched = '1';
                return;
            }

            const row = event.target.closest('.variant-row');
            if (!row) return;

            const nameInput = row.querySelector('.variant-name-input');
            if (!nameInput || nameInput.dataset.touched === '1') return;

            const packSize = row.querySelector('.variant-pack-size-input').value;
            const unitSelect = row.querySelector('.variant-unit-select');
            const shortCode = unitSelect.options[unitSelect.selectedIndex]?.dataset.shortCode;

            if (packSize && shortCode) {
                nameInput.value = `${packSize} ${shortCode}`;
            }
        });
    })();

    (function () {
        const bundleCheckbox = document.getElementById('is_bundle');
        const bundleSection = document.getElementById('bundle-items-section');
        const trackInventory = document.getElementById('track_inventory');

        function syncTrackInventory() {
            if (bundleCheckbox.checked) {
                trackInventory.checked = false;
                trackInventory.disabled = true;
            } else {
                trackInventory.disabled = false;
            }
        }

        bundleCheckbox.addEventListener('change', function () {
            bundleSection.classList.toggle('d-none', !this.checked);
            syncTrackInventory();
        });
        syncTrackInventory();

        let bundleCounter = {{ max((int) collect(array_keys($existingBundleItems))->max(), 0) + 1 }};
        const bundleContainer = document.getElementById('bundle-items-container');
        const bundleTemplate = document.getElementById('bundle-item-row-template');

        document.getElementById('add-bundle-item-row').addEventListener('click', function () {
            const html = bundleTemplate.innerHTML.replaceAll('__INDEX__', bundleCounter++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            bundleContainer.appendChild(wrapper.firstElementChild);
        });

        bundleContainer.addEventListener('click', function (event) {
            const button = event.target.closest('.remove-bundle-item-row');
            if (!button) return;
            button.closest('.bundle-item-row').remove();
        });
    })();
</script>
@endpush
