@extends('layouts.app')

@section('title', $order->shopify_order_number ?? 'Order')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</h1>
            <div class="d-flex gap-2 mt-1">
                <span class="badge {{ $order->channel?->code === 'shopify' ? 'bg-success' : 'bg-info text-dark' }}">{{ $order->channel?->name ?? 'Unknown channel' }}</span>
                <span class="badge {{ match ($order->order_status) {
                    'confirmed' => 'bg-success',
                    'cancelled' => 'bg-danger',
                    default => 'bg-secondary',
                } }}">Order: {{ ucfirst($order->order_status) }}</span>
                <span class="badge {{ match ($order->payment_status) {
                    'paid' => 'bg-success',
                    'refunded' => 'bg-danger',
                    default => 'bg-secondary',
                } }}">Payment: {{ str($order->payment_status)->headline() }}</span>
                @if ($order->payment_type === 'credit')
                    <span class="badge bg-warning text-dark">Credit</span>
                @endif
                <span class="badge bg-secondary">Delivery: {{ str($order->delivery_status)->headline() }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('confirm', $order)
                <form method="POST" action="{{ route('orders.confirm', $order) }}">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Confirm Order</button>
                </form>
            @endcan
            @can('cancel', $order)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modal-cancel-order">
                    Cancel Order
                </button>
            @endcan
            @can('recordPayment', $order)
                @if ($order->total_outstanding > 0)
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-record-payment">
                        <i class="bi bi-cash-coin"></i> Record Payment
                    </button>
                @endif
            @endcan
            @can('delete', $order)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modal-delete-order">
                    <i class="bi bi-trash"></i> Delete Order
                </button>
            @endcan
        </div>
    </div>

    <div class="row g-3">
    <div class="col-lg-8">

    @if ($order->rider_id || $order->canBeAssigned())
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Delivery</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-2">Rider</dt>
                    <dd class="col-sm-4">
                        @if ($order->rider)
                            <a href="{{ route('riders.wallet', $order->rider) }}">{{ $order->rider->user->name }}</a>
                        @else
                            <span class="text-muted">Not yet assigned</span>
                        @endif
                    </dd>
                    <dt class="col-sm-2">COD Amount</dt>
                    <dd class="col-sm-4">
                        {{ $order->cod_amount > 0 ? $order->currency.' '.number_format($order->cod_amount, 2) : '—' }}
                        @if ($order->cod_collected) <span class="badge bg-success">Collected</span> @endif
                    </dd>
                    <dt class="col-sm-2">Assigned</dt>
                    <dd class="col-sm-4">{{ $order->assigned_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    <dt class="col-sm-2">Delivered</dt>
                    <dd class="col-sm-4">{{ $order->delivered_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    <dt class="col-sm-2">Scheduled Dispatch</dt>
                    <dd class="col-sm-4">{{ $order->scheduled_dispatch_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    @if ($order->rider_instructions)
                        <dt class="col-sm-2">Instructions for Rider</dt>
                        <dd class="col-sm-10">{{ $order->rider_instructions }}</dd>
                    @endif
                    @if ($order->delivery_failure_reason)
                        <dt class="col-sm-2">Failure Reason</dt>
                        <dd class="col-sm-10">{{ $order->delivery_failure_reason }}</dd>
                    @endif
                </dl>

                @if ($order->pop_photo_path)
                    @php
                        $popPhotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($order->pop_photo_path);
                    @endphp
                    <div class="mt-3">
                        <div class="small fw-semibold mb-1">Proof of Pickup ({{ $order->pop_captured_at?->format('Y-m-d H:i') }})</div>
                        <div class="d-flex gap-3 flex-wrap">
                            <div>
                                <div class="text-muted small mb-1">Photo</div>
                                <a href="{{ $popPhotoUrl }}" target="_blank">
                                    <img src="{{ $popPhotoUrl }}" alt="Pickup photo"
                                         style="width:140px;height:140px;object-fit:cover;" class="border rounded">
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($order->pod_photo_path || $order->pod_signature_path)
                    @php
                        $podPhotoUrl = $order->pod_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($order->pod_photo_path) : null;
                        $podSignatureUrl = $order->pod_signature_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($order->pod_signature_path) : null;
                    @endphp
                    <div class="mt-3">
                        <div class="small fw-semibold mb-1">Proof of Delivery ({{ $order->pod_captured_at?->format('Y-m-d H:i') }})</div>
                        <div class="d-flex gap-3 flex-wrap">
                            @if ($podPhotoUrl)
                                <div>
                                    <div class="text-muted small mb-1">Photo</div>
                                    <a href="{{ $podPhotoUrl }}" target="_blank">
                                        <img src="{{ $podPhotoUrl }}" alt="Delivery photo"
                                             style="width:140px;height:140px;object-fit:cover;" class="border rounded">
                                    </a>
                                </div>
                            @endif
                            @if ($podSignatureUrl)
                                <div>
                                    <div class="text-muted small mb-1">Signature</div>
                                    <a href="{{ $podSignatureUrl }}" target="_blank">
                                        <img src="{{ $podSignatureUrl }}" alt="Delivery signature"
                                             style="width:140px;height:140px;object-fit:contain;background:#fff;" class="border rounded">
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @can('dispatch.view')
                    <a href="{{ route('dispatch.index') }}" class="small d-inline-block mt-2">Manage on Dispatch Board →</a>
                @endcan
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Customer</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $order->customer_name ?? '—' }}</dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $order->customer_email ?? '—' }}</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $order->customer_phone ?? '—' }}</dd>
                        <dt class="col-sm-4">Order Date</dt>
                        <dd class="col-sm-8">{{ $order->shopify_created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Shipping Address</div>
                <div class="card-body small">
                    @if ($order->shipping_address)
                        {{ $order->shipping_address['address1'] ?? '' }}<br>
                        @if (! empty($order->shipping_address['address2']))
                            {{ $order->shipping_address['address2'] }}<br>
                        @endif
                        {{ $order->shipping_address['city'] ?? '' }}, {{ $order->shipping_address['province'] ?? '' }}<br>
                        {{ $order->shipping_address['country'] ?? '' }} {{ $order->shipping_address['zip'] ?? '' }}
                    @else
                        <span class="text-muted">No shipping address on file.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Items</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                        <th>Matched Locally?</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->sku ?? '—' }}</td>
                            <td class="text-end">{{ rtrim(rtrim($item->quantity, '0'), '.') }}</td>
                            <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_price, 2) }}</td>
                            <td>
                                @if ($item->productVariant)
                                    <span class="badge bg-success">Yes</span>
                                @else
                                    <span class="badge bg-secondary">Unmatched</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal</th>
                        <th class="text-end" colspan="2">{{ number_format($order->subtotal, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Tax</th>
                        <th class="text-end" colspan="2">{{ number_format($order->tax_total, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Shipping</th>
                        <th class="text-end" colspan="2">{{ number_format($order->shipping_total, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end" colspan="2">{{ $order->currency }} {{ number_format($order->total, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if ($order->payment_type === 'credit')
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Payments</span>
                <span class="badge {{ $order->total_outstanding > 0 ? 'bg-warning text-dark' : 'bg-success' }}">
                    Outstanding: {{ $order->currency }} {{ number_format($order->total_outstanding, 2) }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="text-end text-success">+{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ str($payment->method)->headline() }}</td>
                                <td>{{ $payment->reference_number ?? '—' }}</td>
                                <td>{{ $payment->createdBy?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No payments recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm" style="position: sticky; top: 1rem;">
            <div class="card-header bg-white fw-semibold">Order Trail</div>
            <div class="card-body" style="max-height: 75vh; overflow-y: auto;">
                <ul class="list-unstyled mb-0">
                    @forelse ($order->auditLogs as $log)
                        <li class="d-flex gap-2 {{ $loop->last ? '' : 'pb-3' }}">
                            <div class="text-primary pt-1"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i></div>
                            <div class="flex-grow-1">
                                <div class="small">{{ $log->describe() }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    {{ $log->created_at->format('M d, Y H:i') }} &middot; {{ $log->user?->name ?? 'System' }}
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="text-muted small">No activity recorded yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
    </div>

    @can('cancel', $order)
        <div class="modal fade" id="modal-cancel-order" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('orders.cancel', $order) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">Reason</label>
                        <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" required>
                        @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                        <button type="submit" class="btn btn-danger">Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('delete', $order)
        <div class="modal fade" id="modal-delete-order" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('orders.destroy', $order) }}" class="modal-content">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">This permanently removes <strong>{{ $order->shopify_order_number ?? $order->shopify_order_id }}</strong> from the system. Use this only for duplicates, test orders, or mistakes — for a real cancelled order, use Cancel Order instead.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                        <button type="submit" class="btn btn-danger">Confirm Delete</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('recordPayment', $order)
        @if ($order->total_outstanding > 0)
            <div class="modal fade" id="modal-record-payment" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('orders.payments.store', $order) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Record Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex justify-content-between small text-muted mb-3 pb-2 border-bottom">
                                <span>Current Outstanding Balance</span>
                                <span class="fw-semibold text-dark">{{ $order->currency }} {{ number_format($order->total_outstanding, 2) }}</span>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small">Payment Amount *</label>
                                <input type="number" step="0.01" min="0.01" max="{{ $order->total_outstanding }}"
                                       name="amount" id="payment-amount" class="form-control @error('amount') is-invalid @enderror"
                                       value="{{ old('amount') }}" required>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Payment Date *</label>
                                <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror"
                                       value="{{ old('payment_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Payment Method *</label>
                                <select name="method" class="form-select @error('method') is-invalid @enderror" required>
                                    <option value="cash" @selected(old('method', 'cash') === 'cash')>Cash</option>
                                    <option value="bank_transfer" @selected(old('method') === 'bank_transfer')>Bank Transfer</option>
                                    <option value="other" @selected(old('method') === 'other')>Other</option>
                                </select>
                                @error('method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Reference #</label>
                                <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>

                            <div class="d-flex justify-content-between small border-top pt-2 mt-3">
                                <span>Remaining Balance</span>
                                <span class="fw-semibold" id="payment-remaining">{{ $order->currency }} {{ number_format($order->total_outstanding, 2) }}</span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Confirm Payment</button>
                        </div>
                    </form>
                </div>
            </div>

            @push('scripts')
                <script>
                    (function () {
                        var outstanding = {{ (float) $order->total_outstanding }};
                        var amountInput = document.getElementById('payment-amount');
                        var remainingEl = document.getElementById('payment-remaining');

                        amountInput?.addEventListener('input', function () {
                            var amt = parseFloat(this.value) || 0;
                            remainingEl.textContent = '{{ $order->currency }} ' + (outstanding - amt).toFixed(2);
                        });
                    })();
                </script>
            @endpush
        @endif
    @endcan
@endsection
