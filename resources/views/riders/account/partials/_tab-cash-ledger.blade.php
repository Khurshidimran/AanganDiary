<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">Rs. {{ number_format($financials['cod_collected'], 2) }}</div>
                <div class="small text-muted">COD Collected</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">Rs. {{ number_format($financials['cash_deposited'], 2) }}</div>
                <div class="small text-muted">Cash Deposited</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold text-danger">Rs. {{ number_format($financials['cash_to_hand_in'], 2) }}</div>
                <div class="small text-muted">Current Cash to Hand In</div>
            </div>
        </div>
    </div>
</div>

@can('rider_wallet.manage')
    <div class="mb-3">
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-cash-deposit">
            <i class="bi bi-cash-coin"></i> Record Cash Deposit
        </button>
    </div>
@endcan

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order #</th>
                    <th>Transaction Type</th>
                    <th class="text-end">Amount</th>
                    <th>Reference</th>
                    <th class="text-end">Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cashTransactions as $tx)
                    <tr>
                        <td>{{ ($tx->transaction_date ?? $tx->created_at)->format('d M Y') }}</td>
                        <td>{{ $tx->reference_type === 'orders' ? ($cashOrderNumbers[$tx->reference_id] ?? '—') : '—' }}</td>
                        <td>
                            {{ match ($tx->transaction_type) {
                                'cod_collected' => 'COD Collected',
                                'cod_settled' => 'Cash Deposit',
                                'adjustment' => 'Cash Adjustment',
                                default => str($tx->transaction_type)->headline(),
                            } }}
                        </td>
                        <td class="text-end {{ $tx->amount >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $tx->amount >= 0 ? '+' : '' }}Rs. {{ number_format($tx->amount, 2) }}
                        </td>
                        <td>{{ $tx->reference_number ?? '—' }}</td>
                        <td class="text-end">Rs. {{ number_format($tx->balance_after, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No cash transactions yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $cashTransactions->links() }}</div>
