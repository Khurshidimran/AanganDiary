@extends('layouts.app')

@section('title', 'Rider Wallet')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $rider->user->name }}'s Wallet</h1>
            <div class="text-muted small">Positive balance = rider owes company (uncollected COD). Negative = company owes rider (unpaid earnings).</div>
        </div>
        <div>
            <span class="badge fs-6 {{ $rider->wallet_balance > 0 ? 'bg-warning text-dark' : ($rider->wallet_balance < 0 ? 'bg-info text-dark' : 'bg-secondary') }}">
                Balance: {{ number_format($rider->wallet_balance, 2) }}
            </span>
        </div>
    </div>

    @can('rider_wallet.manage')
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Settle COD</h2>
                        <p class="text-muted small">Record cash the rider has handed over.</p>
                        <form method="POST" action="{{ route('riders.wallet.settle-cod', $rider) }}" class="d-flex gap-2">
                            @csrf
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">Settle</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Pay Earnings</h2>
                        <p class="text-muted small">Record cash paid to the rider for credited earnings.</p>
                        <form method="POST" action="{{ route('riders.wallet.pay-earnings', $rider) }}" class="d-flex gap-2">
                            @csrf
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">Pay</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Manual Adjustment</h2>
                        <p class="text-muted small">Freeform correction (use a negative amount to reduce).</p>
                        <form method="POST" action="{{ route('riders.wallet.adjust', $rider) }}">
                            @csrf
                            <div class="d-flex gap-2 mb-2">
                                <input type="number" step="0.01" name="amount" class="form-control form-control-sm" placeholder="Amount" required>
                            </div>
                            <div class="d-flex gap-2">
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Reason" required>
                                <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">Adjust</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Ledger</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Balance After</th>
                        <th>Notes</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                            <td><span class="badge bg-secondary">{{ str($tx->transaction_type)->headline() }}</span></td>
                            <td class="text-end {{ $tx->amount >= 0 ? 'text-danger' : 'text-success' }}">
                                {{ $tx->amount >= 0 ? '+' : '' }}{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="text-end">{{ number_format($tx->balance_after, 2) }}</td>
                            <td>{{ $tx->notes ?? '—' }}</td>
                            <td>{{ $tx->recordedBy?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No wallet activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $transactions->links() }}
    </div>
@endsection
