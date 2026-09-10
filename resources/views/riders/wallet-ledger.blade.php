@extends('layouts.app')

@section('title', $rider->user->name.' — Wallet Ledger')

@php
    $typeMeta = [
        \App\Models\RiderWalletTransaction::TYPE_COD_COLLECTED => ['label' => 'COD Collected', 'badge' => 'bg-primary'],
        \App\Models\RiderWalletTransaction::TYPE_COD_SETTLED => ['label' => 'Cash Deposit', 'badge' => 'bg-success'],
        \App\Models\RiderWalletTransaction::TYPE_EARNING_CREDITED => ['label' => 'Earning Credited', 'badge' => 'bg-info text-dark'],
        \App\Models\RiderWalletTransaction::TYPE_EARNING_PAID => ['label' => 'Earning Paid', 'badge' => 'bg-secondary'],
        \App\Models\RiderWalletTransaction::TYPE_ADJUSTMENT => ['label' => 'Adjustment', 'badge' => 'bg-warning text-dark'],
    ];
    $orderTypeLabel = fn (?string $type) => match ($type) {
        \App\Models\Order::ORDER_TYPE_SELF_PICKUP => 'Self Pickup',
        \App\Models\Order::ORDER_TYPE_DELIVERY => 'Delivery',
        default => '—',
    };
@endphp

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $rider->user->name }} — Wallet Ledger</h1>
            <div class="text-muted small">Every transaction that ever moved this rider's wallet balance, in one place.</div>
        </div>
        <a href="{{ route('riders.wallet', $rider) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Rider Account
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('riders.wallet-ledger', $rider) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom?->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo?->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    @if ($dateFrom || $dateTo)
                        <a href="{{ route('riders.wallet-ledger', $rider) }}" class="btn btn-sm btn-link">All Time</a>
                    @else
                        <span class="small text-muted">Showing all time</span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-1">Opening Balance</div>
                    <div class="fs-3 fw-bold">Rs. {{ number_format($openingBalance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-1">Closing Balance</div>
                    <div class="fs-3 fw-bold {{ $closingBalance < 0 ? 'text-danger' : '' }}">Rs. {{ number_format($closingBalance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Order #</th>
                        <th>Order Type</th>
                        <th>Transaction Type</th>
                        <th class="text-end">Amount</th>
                        <th>Reference</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        @php $order = $tx->reference_type === 'orders' ? ($orders[$tx->reference_id] ?? null) : null; @endphp
                        <tr>
                            <td class="text-nowrap">{{ ($tx->transaction_date ?? $tx->created_at)->format('d M Y') }}</td>
                            <td>{{ $order?->shopify_order_number ?? '—' }}</td>
                            <td>{{ $orderTypeLabel($order?->order_type) }}</td>
                            <td>
                                <span class="badge {{ $typeMeta[$tx->transaction_type]['badge'] ?? 'bg-secondary' }}">
                                    {{ $typeMeta[$tx->transaction_type]['label'] ?? str($tx->transaction_type)->headline() }}
                                </span>
                            </td>
                            <td class="text-end {{ $tx->amount >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $tx->amount >= 0 ? '+' : '' }}Rs. {{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="text-muted small">{{ $tx->reference_number ?? $tx->notes ?? '—' }}</td>
                            <td class="text-end fw-semibold {{ $tx->balance_after < 0 ? 'text-danger' : '' }}">Rs. {{ number_format($tx->balance_after, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No wallet activity in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
