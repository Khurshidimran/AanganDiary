@extends('layouts.app')

@section('title', 'Order '.($order->shopify_order_number ?? $order->shopify_order_id).' — Delivery Attempts')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">Order {{ $order->shopify_order_number ?? $order->shopify_order_id }}</h1>
            <div class="text-muted small">
                {{ $attempts->count() }} delivery attempt{{ $attempts->count() === 1 ? '' : 's' }}
                &middot; {{ $order->customer_name ?? 'Unknown customer' }}
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-secondary">View Full Order</a>
            <a href="{{ route('riders.wallet', $rider) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Rider Account
            </a>
        </div>
    </div>

    <div class="alert alert-info py-2 px-3 small">
        <i class="bi bi-info-circle"></i> This order number stays the same across every attempt below — only the assigned rider and outcome change.
    </div>

    @foreach ($attempts as $attempt)
        @php
            $steps = collect([
                ['label' => 'Assigned', 'at' => $attempt->assigned_at],
                ['label' => 'Picked Up', 'at' => $attempt->picked_up_at],
                ['label' => 'Out for Delivery', 'at' => $attempt->out_for_delivery_at],
            ]);

            if ($attempt->status === 'delivered') {
                $steps->push(['label' => 'Delivered', 'at' => $attempt->delivered_at]);
            } elseif (in_array($attempt->status, ['failed', 'returned'], true)) {
                $steps->push(['label' => 'Delivery Failed', 'at' => $attempt->completed_at]);

                if ($attempt->status === 'returned') {
                    $steps->push(['label' => 'Returned to Warehouse', 'at' => $attempt->completed_at]);
                }
            }
        @endphp
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="fw-semibold">Attempt #{{ $attempt->attempt_number }}</span>
                    <span class="text-muted small"> &middot; Rider: {{ $attempt->rider?->user->name ?? '—' }}</span>
                    <span class="text-muted small"> &middot; {{ $attempt->assigned_at?->format('d M Y') ?? '—' }}</span>
                </div>
                @include('riders.account.partials._status-badge', ['status' => $attempt->status])
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    @foreach ($steps as $step)
                        <li class="d-flex gap-2 {{ $loop->last ? '' : 'pb-2' }}">
                            <div class="text-primary pt-1"><i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i></div>
                            <div class="flex-grow-1 d-flex justify-content-between">
                                <span class="small">{{ $step['label'] }}</span>
                                <span class="text-muted small">{{ $step['at']?->format('d M, h:i A') ?? '—' }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($attempt->status === 'failed' && $attempt->failure_reason)
                    <div class="alert alert-danger py-2 px-3 small mt-3 mb-0">
                        Failure Reason: {{ $attempt->failure_reason }}
                    </div>
                @elseif ($attempt->status === 'returned')
                    <div class="alert alert-warning py-2 px-3 small mt-3 mb-0">
                        Returned to warehouse — stock released back to inventory.
                        @if ($attempt->failure_reason)
                            Original failure reason: {{ $attempt->failure_reason }}.
                        @endif
                    </div>
                @elseif ($attempt->status === 'delivered')
                    <div class="alert alert-success py-2 px-3 small mt-3 mb-0">
                        COD Collected: {{ $attempt->cod_collected ? 'Rs. '.number_format($attempt->cod_amount, 2) : 'Not collected' }}
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endsection
