@can('purchase_orders.view')
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Purchase Order Coverage</span>
            <a href="{{ route('purchase-orders.index') }}" class="small">View all Purchase Orders <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                This business holds no standing inventory — every sale order that has anything purchasable auto-generates its own draft Purchase Order.
                Of the {{ $purchaseOrderCoverage['total_orders'] }} orders placed in this window, <strong>{{ $purchaseOrderCoverage['orders_with_po'] }}</strong> triggered one.
            </p>
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <div class="fs-4 fw-bold text-warning">{{ $purchaseOrderCoverage['pending_count'] }}</div>
                        <div class="small text-muted">Pending</div>
                        <div class="small text-muted">Rs. {{ number_format($purchaseOrderCoverage['pending_amount'], 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <div class="fs-4 fw-bold text-success">{{ $purchaseOrderCoverage['closed_count'] }}</div>
                        <div class="small text-muted">Closed (Fully Received)</div>
                        <div class="small text-muted">Rs. {{ number_format($purchaseOrderCoverage['closed_amount'], 2) }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="border rounded p-3 text-center h-100">
                        <div class="fs-4 fw-bold text-danger">{{ $purchaseOrderCoverage['cancelled_count'] }}</div>
                        <div class="small text-muted">Cancelled</div>
                        <div class="small text-muted">Rs. {{ number_format($purchaseOrderCoverage['cancelled_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
