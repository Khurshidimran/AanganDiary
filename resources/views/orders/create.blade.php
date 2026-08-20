@extends('layouts.app')

@section('title', 'New Order')

@section('content')
    <h1 class="h4 mb-3">New Order</h1>
    <p class="text-muted small mb-3">For orders taken by phone, WhatsApp, or any channel other than Shopify. It's created as pending — confirm it from the order page when you're ready to allocate stock.</p>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('orders.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="channel_id" class="form-label">Channel</label>
                        <select id="channel_id" name="channel_id" class="form-select @error('channel_id') is-invalid @enderror" required>
                            <option value="">Select a channel</option>
                            @foreach ($channels as $channel)
                                <option value="{{ $channel->id }}" @selected((string) old('channel_id') === (string) $channel->id)>{{ $channel->name }}</option>
                            @endforeach
                        </select>
                        @error('channel_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($channels->isEmpty())
                            <div class="form-text text-warning">No channels available — <a href="{{ route('channels.create') }}">create one</a> first.</div>
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select id="payment_status" name="payment_status" class="form-select @error('payment_status') is-invalid @enderror">
                            @foreach (['pending' => 'Pending (COD)', 'paid' => 'Paid', 'partially_paid' => 'Partially Paid'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('payment_status', 'pending') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('payment_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <h2 class="h6">Customer</h2>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="customer_name" class="form-label">Name</label>
                        <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}"
                               class="form-control @error('customer_name') is-invalid @enderror" required>
                        @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="customer_phone" class="form-label">Phone</label>
                        <input id="customer_phone" type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                               class="form-control @error('customer_phone') is-invalid @enderror" required>
                        @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="customer_email" class="form-label">Email (optional)</label>
                        <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}"
                               class="form-control @error('customer_email') is-invalid @enderror">
                        @error('customer_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <h2 class="h6">Delivery Address</h2>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="address1" class="form-label">Address Line 1</label>
                        <input id="address1" type="text" name="address1" value="{{ old('address1') }}"
                               class="form-control @error('address1') is-invalid @enderror" required>
                        @error('address1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="address2" class="form-label">Address Line 2 (optional)</label>
                        <input id="address2" type="text" name="address2" value="{{ old('address2') }}" class="form-control">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="city" class="form-label">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}"
                               class="form-control @error('city') is-invalid @enderror" required>
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="country" class="form-label">Country</label>
                        <input id="country" type="text" name="country" value="{{ old('country', 'Pakistan') }}"
                               class="form-control @error('country') is-invalid @enderror" required>
                        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    @include('orders._item-row', ['index' => 0, 'item' => null, 'variants' => $variants])
                </div>

                <template id="item-row-template">
                    @include('orders._item-row', ['index' => '__INDEX__', 'item' => null, 'variants' => $variants])
                </template>

                <div class="row justify-content-end mt-3">
                    <div class="col-md-5">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Discount Total</label>
                                <input type="number" step="0.01" min="0" name="discount_total" id="discount_total" value="{{ old('discount_total', 0) }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Shipping Total</label>
                                <input type="number" step="0.01" min="0" name="shipping_total" id="shipping_total" value="{{ old('shipping_total', 0) }}" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small">Tax Total</label>
                                <input type="number" step="0.01" min="0" name="tax_total" id="tax_total" value="{{ old('tax_total', 0) }}" class="form-control form-control-sm">
                            </div>
                        </div>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>Subtotal</td>
                                <td class="text-end" id="summary-subtotal">0.00</td>
                            </tr>
                            <tr class="fw-semibold">
                                <td>Total</td>
                                <td class="text-end" id="summary-total">0.00</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="{{ route('orders.index') }}" class="btn btn-link">Cancel</a>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                let counter = 1;
                const container = document.getElementById('items-container');
                const template = document.getElementById('item-row-template');

                function money(value) {
                    return (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);
                }

                function recalcTotals() {
                    let subtotal = 0;

                    container.querySelectorAll('.item-row').forEach((row) => {
                        const qty = parseFloat(row.querySelector('.item-quantity-input').value) || 0;
                        const price = parseFloat(row.querySelector('.item-unit-price-input').value) || 0;
                        const lineTotal = qty * price;
                        row.querySelector('.item-line-total').value = money(lineTotal);
                        subtotal += lineTotal;
                    });

                    const discount = parseFloat(document.getElementById('discount_total').value) || 0;
                    const shipping = parseFloat(document.getElementById('shipping_total').value) || 0;
                    const tax = parseFloat(document.getElementById('tax_total').value) || 0;
                    const total = subtotal - discount + shipping + tax;

                    document.getElementById('summary-subtotal').textContent = money(subtotal);
                    document.getElementById('summary-total').textContent = money(total);
                }

                document.getElementById('add-item-row').addEventListener('click', function () {
                    const html = template.innerHTML.replaceAll('__INDEX__', counter++);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    container.appendChild(wrapper.firstElementChild);
                    recalcTotals();
                });

                container.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-item-row');
                    if (!button) return;

                    const rows = container.querySelectorAll('.item-row');
                    if (rows.length <= 1) {
                        alert('An order must have at least one item.');
                        return;
                    }

                    button.closest('.item-row').remove();
                    recalcTotals();
                });

                // Auto-fill unit price from the variant's sale price when first selected.
                container.addEventListener('change', function (event) {
                    if (event.target.classList.contains('item-variant-select')) {
                        const row = event.target.closest('.item-row');
                        const priceInput = row.querySelector('.item-unit-price-input');

                        if (!priceInput.value) {
                            const price = event.target.options[event.target.selectedIndex]?.dataset.salePrice;
                            if (price) priceInput.value = price;
                        }
                    }

                    recalcTotals();
                });

                container.addEventListener('input', recalcTotals);
                ['discount_total', 'shipping_total', 'tax_total'].forEach((id) => {
                    document.getElementById(id).addEventListener('input', recalcTotals);
                });

                recalcTotals();
            })();
        </script>
    @endpush
@endsection
