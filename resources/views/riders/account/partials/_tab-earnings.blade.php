<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">Rs. {{ number_format($financials['earnings_earned'], 2) }}</div>
                <div class="small text-muted">Delivery Charges Earned</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold">Rs. {{ number_format($financials['earnings_paid'], 2) }}</div>
                <div class="small text-muted">Delivery Charges Paid</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center h-100">
            <div class="card-body">
                <div class="fs-4 fw-bold text-primary">Rs. {{ number_format($financials['earnings_payable'], 2) }}</div>
                <div class="small text-muted">Earnings Payable</div>
            </div>
        </div>
    </div>
</div>

@can('rider_wallet.manage')
    <div class="mb-3">
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal-pay-rider">
            <i class="bi bi-wallet2"></i> Pay Rider
        </button>
    </div>
@endcan

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Delivery Attempt</th>
                    <th>Delivery Date</th>
                    <th class="text-end">Delivery Charge</th>
                    <th>Payment Status</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($earningsAttempts as $attempt)
                    @php $ps = $paymentStatus->get($attempt->id, ['status' => 'unpaid', 'paid_at' => null]); @endphp
                    <tr>
                        <td>{{ $attempt->order->shopify_order_number ?? $attempt->order->shopify_order_id }}</td>
                        <td>Attempt #{{ $attempt->attempt_number }}</td>
                        <td>{{ $attempt->delivered_at?->format('d M Y') ?? '—' }}</td>
                        <td class="text-end">Rs. {{ number_format($attempt->delivery_charge, 2) }}</td>
                        <td>
                            <span class="badge {{ $ps['status'] === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($ps['status']) }}
                            </span>
                        </td>
                        <td>{{ $ps['paid_at'] ? \Carbon\Carbon::parse($ps['paid_at'])->format('d M Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No delivery earnings recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $earningsAttempts->links() }}</div>
