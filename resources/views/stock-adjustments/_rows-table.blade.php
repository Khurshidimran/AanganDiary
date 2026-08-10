@if ($rows->isEmpty())
    <div class="alert alert-info">No active products match this filter.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Variant</th>
                    <th>Current Stock</th>
                    <th style="width: 12%;">Batch Number</th>
                    <th style="width: 10%;">Direction</th>
                    <th style="width: 13%;">Reason</th>
                    <th style="width: 10%;">Quantity</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php
                        $variant = $row['variant'];
                        $existing = $row['existing'];
                        $get = function ($key, $default = '') use ($existing, $variant) {
                            $value = old("items.{$variant->id}.{$key}", $existing?->{$key} ?? $default);

                            return $key === 'quantity' && $value !== '' ? rtrim(rtrim((string) $value, '0'), '.') : $value;
                        };
                    @endphp
                    <tr>
                        <td>{{ $variant->product->name }}</td>
                        <td>{{ $variant->name }} ({{ $variant->sku }})</td>
                        <td>
                            {{ $row['current_stock'] }} {{ $variant->unit->short_code }}
                            @if ($row['batches']->isNotEmpty())
                                <div class="small text-muted">
                                    @foreach ($row['batches'] as $batch)
                                        {{ $batch['batch'] }}: {{ $batch['qty'] }}@if (! $loop->last), @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            <input type="text" name="items[{{ $variant->id }}][batch_number]" value="{{ $get('batch_number') }}"
                                   class="form-control form-control-sm">
                        </td>
                        <td>
                            <select name="items[{{ $variant->id }}][direction]" class="form-select form-select-sm">
                                <option value="increase" @selected($get('direction', 'decrease') === 'increase')>Increase</option>
                                <option value="decrease" @selected($get('direction', 'decrease') === 'decrease')>Decrease</option>
                            </select>
                        </td>
                        <td>
                            <select name="items[{{ $variant->id }}][reason]" class="form-select form-select-sm">
                                <option value="stock_adjustment" @selected($get('reason', 'stock_adjustment') === 'stock_adjustment')>Correction</option>
                                <option value="wastage" @selected($get('reason', 'stock_adjustment') === 'wastage')>Wastage</option>
                                <option value="damage" @selected($get('reason', 'stock_adjustment') === 'damage')>Damage</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.001" min="0" name="items[{{ $variant->id }}][quantity]" value="{{ $get('quantity') }}"
                                   class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="text" name="items[{{ $variant->id }}][notes]" value="{{ $get('notes') }}"
                                   class="form-control form-control-sm">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="form-text mb-3">Only rows with a quantity entered will be saved.</div>
@endif
