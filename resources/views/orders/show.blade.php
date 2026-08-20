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
                <form method="POST" action="{{ route('orders.cancel', $order) }}"
                      onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Cancel Order</button>
                </form>
            @endcan
        </div>
    </div>

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
@endsection
