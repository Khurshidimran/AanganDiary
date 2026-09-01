@extends('layouts.app')

@section('title', 'New Order')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
@endpush

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
                    <div class="col-md-4 mb-3">
                        <label for="payment_type" class="form-label">Payment Type</label>
                        <select id="payment_type" name="payment_type" class="form-select @error('payment_type') is-invalid @enderror">
                            <option value="cash" @selected(old('payment_type', 'cash') === 'cash')>Cash / COD</option>
                            <option value="credit" @selected(old('payment_type') === 'credit')>Credit (pay later)</option>
                        </select>
                        <div class="form-text">Credit orders appear on the Receivables Aging report until fully paid.</div>
                        @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label d-block">Order Type</label>
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="order_type" id="order_type_delivery" value="delivery" autocomplete="off" @checked(old('order_type', 'delivery') === 'delivery')>
                            <label class="btn btn-outline-secondary btn-sm" for="order_type_delivery">Delivery</label>

                            <input type="radio" class="btn-check" name="order_type" id="order_type_self_pickup" value="self_pickup" autocomplete="off" @checked(old('order_type') === 'self_pickup')>
                            <label class="btn btn-outline-secondary btn-sm" for="order_type_self_pickup">Self Pickup</label>
                        </div>
                        <div class="form-text">Self pickup skips the Dispatch Board entirely and waives the shipping charge.</div>
                        @error('order_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Customer</h2>
                    <button type="button" id="new-customer-btn" class="btn btn-sm btn-link d-none">+ New Customer</button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="customer_search" class="form-label">Find Existing Customer</label>
                        <select id="customer_search" placeholder="Search by name or phone number..."></select>
                        <div class="form-text">Can't find them? Just fill in the fields below for a new customer.</div>
                    </div>
                </div>
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
                <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id') }}">

                <h2 class="h6">Delivery Address</h2>
                <div class="row d-none" id="address-select-wrapper">
                    <div class="col-md-6 mb-3">
                        <label for="customer_address_select" class="form-label">Saved Addresses</label>
                        <select id="customer_address_select" class="form-select"></select>
                    </div>
                </div>
                <input type="hidden" id="customer_address_id" name="customer_address_id" value="{{ old('customer_address_id') }}">

                <div id="new-address-fields">
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
                            <select id="city" name="city" class="form-select @error('city') is-invalid @enderror" required>
                                <option value="">Select a city</option>
                                @foreach (['Lahore'] as $cityOption)
                                    <option value="{{ $cityOption }}" @selected(old('city') === $cityOption)>{{ $cityOption }}</option>
                                @endforeach
                            </select>
                            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label">Country</label>
                            <input id="country" type="text" name="country" value="{{ old('country', 'Pakistan') }}"
                                   class="form-control @error('country') is-invalid @enderror" required>
                            @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
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
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            (function () {
                let counter = 1;
                const container = document.getElementById('items-container');
                const template = document.getElementById('item-row-template');
                const newAddressFields = document.getElementById('new-address-fields');
                const addressSelectWrapper = document.getElementById('address-select-wrapper');
                const addressSelect = document.getElementById('customer_address_select');
                const addressRequiredInputs = ['address1', 'city', 'country'].map((id) => document.getElementById(id));

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

                // ---- Product picker: searchable (Tom-Select, local/instant-filter
                // mode — only ~50 variants, no need for a server round-trip) and
                // always syncs Unit Price to whatever's currently selected. ----
                function initVariantSelect(selectEl) {
                    const ts = new TomSelect(selectEl, {maxOptions: null});

                    ts.on('change', function (value) {
                        const row = selectEl.closest('.item-row');
                        const priceInput = row.querySelector('.item-unit-price-input');
                        const option = selectEl.querySelector(`option[value="${value}"]`);
                        const price = option?.dataset.salePrice;

                        if (price) {
                            priceInput.value = price;
                        }

                        recalcTotals();
                    });

                    return ts;
                }

                initVariantSelect(container.querySelector('.item-variant-select'));

                document.getElementById('add-item-row').addEventListener('click', function () {
                    const html = template.innerHTML.replaceAll('__INDEX__', counter++);
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    const row = wrapper.firstElementChild;
                    container.appendChild(row);
                    initVariantSelect(row.querySelector('.item-variant-select'));
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

                    const row = button.closest('.item-row');
                    row.querySelector('.item-variant-select')?.tomselect?.destroy();
                    row.remove();
                    recalcTotals();
                });

                container.addEventListener('input', recalcTotals);
                ['discount_total', 'shipping_total', 'tax_total'].forEach((id) => {
                    document.getElementById(id).addEventListener('input', recalcTotals);
                });

                // ---- Delivery address: shown as a saved-addresses picker once an
                // existing customer with at least one address is selected, or as a
                // plain new-address form otherwise (fresh customer, or "+ Add a new
                // address" chosen). required attributes toggle with visibility so a
                // hidden field never silently blocks submission. ----
                function showNewAddressFields() {
                    newAddressFields.classList.remove('d-none');
                    addressRequiredInputs.forEach((el) => el.setAttribute('required', 'required'));
                }

                function hideNewAddressFields() {
                    newAddressFields.classList.add('d-none');
                    addressRequiredInputs.forEach((el) => el.removeAttribute('required'));
                }

                function populateAddressSelect(addresses) {
                    addressSelect.innerHTML = '';

                    if (!addresses || addresses.length === 0) {
                        addressSelectWrapper.classList.add('d-none');
                        document.getElementById('customer_address_id').value = '';
                        showNewAddressFields();
                        return;
                    }

                    addresses.forEach((address) => {
                        const option = document.createElement('option');
                        option.value = address.id;
                        option.textContent = address.label + (address.is_default ? ' (default)' : '');
                        addressSelect.appendChild(option);
                    });

                    const addNewOption = document.createElement('option');
                    addNewOption.value = '';
                    addNewOption.textContent = '+ Add a new address';
                    addressSelect.appendChild(addNewOption);

                    const defaultAddress = addresses.find((a) => a.is_default) || addresses[0];
                    addressSelect.value = defaultAddress.id;
                    document.getElementById('customer_address_id').value = defaultAddress.id;

                    addressSelectWrapper.classList.remove('d-none');
                    hideNewAddressFields();
                }

                addressSelect.addEventListener('change', function () {
                    if (this.value) {
                        document.getElementById('customer_address_id').value = this.value;
                        hideNewAddressFields();
                    } else {
                        document.getElementById('customer_address_id').value = '';
                        showNewAddressFields();
                    }
                });

                // ---- Customer search: searchable (Tom-Select, remote mode — calls
                // customers.search) by name or phone. Selecting a result fills
                // Name/Phone/Email (still editable, e.g. to correct a typo) and
                // the address picker above. ----
                const customerSearch = new TomSelect('#customer_search', {
                    valueField: 'value',
                    labelField: 'text',
                    searchField: ['text'],
                    // The server (customers.search) already filters by name
                    // OR phone — without this, Tom-Select's own local fuzzy
                    // scoring re-filters whatever load() returns against the
                    // typed query, and can silently hide a real match (e.g.
                    // a name search scored poorly against the combined
                    // "Name — Phone" display text). Trust the server's
                    // results as-is instead of re-filtering them client-side.
                    score: function () {
                        return function () {
                            return 1;
                        };
                    },
                    load: function (query, callback) {
                        if (!query.length) {
                            callback();
                            return;
                        }

                        fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(query)}`, {
                            headers: {'X-Requested-With': 'XMLHttpRequest'},
                        })
                            .then((response) => response.json())
                            .then((json) => callback(json))
                            .catch(() => callback());
                    },
                });

                customerSearch.on('change', function (value) {
                    if (!value) return;

                    const data = this.options[value];
                    if (!data) return;

                    document.getElementById('customer_id').value = data.value;
                    document.getElementById('customer_name').value = data.name;
                    document.getElementById('customer_phone').value = data.phone;
                    document.getElementById('customer_email').value = data.email || '';
                    document.getElementById('new-customer-btn').classList.remove('d-none');

                    populateAddressSelect(data.addresses);
                });

                document.getElementById('new-customer-btn').addEventListener('click', function () {
                    customerSearch.clear();
                    document.getElementById('customer_id').value = '';
                    document.getElementById('customer_name').value = '';
                    document.getElementById('customer_phone').value = '';
                    document.getElementById('customer_email').value = '';
                    this.classList.add('d-none');
                    populateAddressSelect([]);
                });

                // ---- Order Type: Self Pickup waives the shipping charge --
                // clear and lock the field rather than just hiding it, so a
                // stale value from before switching can't sneak into the total. ----
                const shippingTotalInput = document.getElementById('shipping_total');
                const shippingTotalPrevious = {value: shippingTotalInput.value};

                function applyOrderTypeToggle() {
                    const isSelfPickup = document.getElementById('order_type_self_pickup').checked;

                    if (isSelfPickup) {
                        shippingTotalPrevious.value = shippingTotalInput.value;
                        shippingTotalInput.value = 0;
                        shippingTotalInput.disabled = true;
                    } else {
                        shippingTotalInput.disabled = false;
                        shippingTotalInput.value = shippingTotalPrevious.value;
                    }

                    recalcTotals();
                }

                document.querySelectorAll('input[name="order_type"]').forEach((input) => {
                    input.addEventListener('change', applyOrderTypeToggle);
                });
                applyOrderTypeToggle();

                recalcTotals();
            })();
        </script>
    @endpush
@endsection
