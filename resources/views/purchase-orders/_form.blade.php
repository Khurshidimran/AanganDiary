@php
    $existingItems = old('items', $purchaseOrder?->items->map->only(['product_variant_id', 'quantity_ordered', 'unit_cost'])->all() ?? []);
@endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="vendor_id" class="form-label">Vendor</label>
        <select id="vendor_id" name="vendor_id" class="form-select @error('vendor_id') is-invalid @enderror" required>
            <option value="">Select a vendor</option>
            @foreach ($vendors as $id => $name)
                <option value="{{ $id }}" @selected((string) old('vendor_id', $purchaseOrder?->vendor_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="warehouse_id" class="form-label">Deliver To Warehouse</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>
            <option value="">Select a warehouse</option>
            @foreach ($warehouses as $id => $name)
                <option value="{{ $id }}" @selected((string) old('warehouse_id', $purchaseOrder?->warehouse_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2 mb-3">
        <label for="order_date" class="form-label">Order Date</label>
        <input id="order_date" type="date" name="order_date" value="{{ old('order_date', $purchaseOrder?->order_date?->toDateString() ?? now()->toDateString()) }}"
               class="form-control @error('order_date') is-invalid @enderror" required>
        @error('order_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2 mb-3">
        <label for="expected_date" class="form-label">Expected Date</label>
        <input id="expected_date" type="date" name="expected_date" value="{{ old('expected_date', $purchaseOrder?->expected_date?->toDateString()) }}"
               class="form-control @error('expected_date') is-invalid @enderror">
        @error('expected_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchaseOrder?->notes) }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<hr>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h2 class="h6 mb-0">Items</h2>
    <button type="button" id="add-item-row" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-plus-lg"></i> Add Item
    </button>
</div>
@error('items') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

<div id="items-container">
    @forelse ($existingItems as $index => $item)
        @include('purchase-orders._item-row', ['index' => $index, 'item' => $item, 'variants' => $variants])
    @empty
        @include('purchase-orders._item-row', ['index' => 0, 'item' => null, 'variants' => $variants])
    @endforelse
</div>

<template id="item-row-template">
    @include('purchase-orders._item-row', ['index' => '__INDEX__', 'item' => null, 'variants' => $variants])
</template>

@push('scripts')
<script>
    (function () {
        let counter = {{ max((int) collect(array_keys($existingItems))->max(), 0) + 1 }};
        const container = document.getElementById('items-container');
        const template = document.getElementById('item-row-template');

        document.getElementById('add-item-row').addEventListener('click', function () {
            const html = template.innerHTML.replaceAll('__INDEX__', counter++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            container.appendChild(wrapper.firstElementChild);
        });

        container.addEventListener('click', function (event) {
            const button = event.target.closest('.remove-item-row');
            if (!button) return;

            const rows = container.querySelectorAll('.item-row');
            if (rows.length <= 1) {
                alert('A purchase order must have at least one item.');
                return;
            }

            button.closest('.item-row').remove();
        });

        // Auto-fill unit cost from the variant's purchase price when first selected.
        container.addEventListener('change', function (event) {
            if (!event.target.classList.contains('item-variant-select')) return;

            const row = event.target.closest('.item-row');
            const costInput = row.querySelector('.item-unit-cost-input');
            if (costInput.value) return;

            const price = event.target.options[event.target.selectedIndex]?.dataset.purchasePrice;
            if (price) costInput.value = price;
        });
    })();
</script>
@endpush
