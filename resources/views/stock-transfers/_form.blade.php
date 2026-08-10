@php
    $existingItems = old('items', $stockTransfer?->items->map->only(['product_variant_id', 'batch_number', 'quantity', 'unit_cost'])->all() ?? []);
@endphp

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="from_warehouse_id" class="form-label">From Warehouse</label>
        <select id="from_warehouse_id" name="from_warehouse_id" class="form-select @error('from_warehouse_id') is-invalid @enderror" required>
            <option value="">Select a warehouse</option>
            @foreach ($warehouses as $id => $name)
                <option value="{{ $id }}" @selected((string) old('from_warehouse_id', $stockTransfer?->from_warehouse_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('from_warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="to_warehouse_id" class="form-label">To Warehouse</label>
        <select id="to_warehouse_id" name="to_warehouse_id" class="form-select @error('to_warehouse_id') is-invalid @enderror" required>
            <option value="">Select a warehouse</option>
            @foreach ($warehouses as $id => $name)
                <option value="{{ $id }}" @selected((string) old('to_warehouse_id', $stockTransfer?->to_warehouse_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('to_warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="transfer_date" class="form-label">Transfer Date</label>
        <input id="transfer_date" type="date" name="transfer_date" value="{{ old('transfer_date', $stockTransfer?->transfer_date?->toDateString() ?? now()->toDateString()) }}"
               class="form-control @error('transfer_date') is-invalid @enderror" required>
        @error('transfer_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $stockTransfer?->notes) }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="card bg-body-tertiary mb-3" id="source-stock-hint" style="display:none;">
    <div class="card-body py-2">
        <div class="small fw-semibold mb-1">Current stock at selected source warehouse</div>
        <div id="source-stock-list" class="small text-muted"></div>
    </div>
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
        @include('stock-transfers._item-row', ['index' => $index, 'item' => $item, 'variants' => $variants])
    @empty
        @include('stock-transfers._item-row', ['index' => 0, 'item' => null, 'variants' => $variants])
    @endforelse
</div>

<template id="item-row-template">
    @include('stock-transfers._item-row', ['index' => '__INDEX__', 'item' => null, 'variants' => $variants])
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
                alert('A stock transfer must have at least one item.');
                return;
            }

            button.closest('.item-row').remove();
        });

        const stockByWarehouse = @json($stockByWarehouseJson);

        const fromSelect = document.getElementById('from_warehouse_id');
        const hint = document.getElementById('source-stock-hint');
        const list = document.getElementById('source-stock-list');

        function renderStockHint() {
            const items = stockByWarehouse[fromSelect.value];
            if (!items || items.length === 0) {
                hint.style.display = 'none';
                return;
            }

            list.innerHTML = items.map(function (i) {
                const batch = i.batch ? ` (batch ${i.batch})` : '';
                return `${i.name} (${i.sku})${batch}: <strong>${i.qty}</strong>`;
            }).join(' &nbsp;|&nbsp; ');

            hint.style.display = 'block';
        }

        fromSelect.addEventListener('change', renderStockHint);
        renderStockHint();

        // Prefill unit cost from the last purchase price when a variant is chosen, if the field is still empty.
        container.addEventListener('change', function (event) {
            if (!event.target.classList.contains('item-variant-select')) return;

            const row = event.target.closest('.item-row');
            const costInput = row.querySelector('.item-unit-cost-input');
            if (costInput.value) return;

            const lastPrice = event.target.options[event.target.selectedIndex]?.dataset.lastPrice;
            if (lastPrice) costInput.value = lastPrice;
        });
    })();
</script>
@endpush
