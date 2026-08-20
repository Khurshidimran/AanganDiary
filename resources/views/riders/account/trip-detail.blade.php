@extends('layouts.app')

@section('title', 'Trip Detail — '.$rider->user->name)

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h5 mb-0">Trip &middot; {{ $rider->user->name }}</h1>
            <div class="text-muted small">
                {{ $trip->checked_in_at->format('d M Y') }}
                &middot; Check In {{ $trip->checked_in_at->format('h:i A') }}
                &middot; Checkout {{ $trip->checked_out_at?->format('h:i A') ?? 'Still active' }}
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge {{ $trip->status === 'active' ? 'bg-primary' : 'bg-success' }}">
                {{ $trip->status === 'active' ? 'Active' : 'Completed' }}
            </span>
            <a href="{{ route('riders.wallet', $rider) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Rider Account
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Attempt</th>
                        <th>Status</th>
                        <th class="text-end">COD Amount</th>
                        <th>Picked Up</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attempts as $attempt)
                        <tr>
                            <td>
                                <a href="{{ route('riders.wallet.order-attempts', [$rider, $attempt->order]) }}">
                                    {{ $attempt->order->shopify_order_number ?? $attempt->order->shopify_order_id }}
                                </a>
                            </td>
                            <td>{{ $attempt->order->customer_name ?? '—' }}</td>
                            <td>Attempt #{{ $attempt->attempt_number }}</td>
                            <td>@include('riders.account.partials._status-badge', ['status' => $attempt->status])</td>
                            <td class="text-end">{{ $attempt->cod_amount ? 'Rs. '.number_format($attempt->cod_amount, 2) : '—' }}</td>
                            <td>{{ $attempt->picked_up_at?->format('h:i A') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No orders were picked up during this trip.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
