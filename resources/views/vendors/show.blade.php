@extends('layouts.app')

@section('title', $vendor->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $vendor->name }}</h1>
            <div class="text-muted small">{{ $vendor->company_name }}</div>
        </div>
        @can('update', $vendor)
            <a href="{{ route('vendors.edit', $vendor) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Opening Balance</div>
                    <div class="fs-5 fw-bold">{{ number_format($vendor->opening_balance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Goods Received</div>
                    <div class="fs-5 fw-bold">{{ number_format($vendor->totalReceived(), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="text-muted small">Total Paid</div>
                <div class="card-body">
                    <div class="fs-5 fw-bold">{{ number_format($vendor->totalPaid(), 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <div class="text-muted small">Payable Balance</div>
                    <div class="fs-5 fw-bold">{{ number_format($vendor->payableBalance(), 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Contact Details</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Contact Person</dt>
                        <dd class="col-sm-8">{{ $vendor->contact_person ?? '—' }}</dd>
                        <dt class="col-sm-4">Phone</dt>
                        <dd class="col-sm-8">{{ $vendor->phone ?? '—' }}</dd>
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $vendor->email ?? '—' }}</dd>
                        <dt class="col-sm-4">Address</dt>
                        <dd class="col-sm-8">{{ $vendor->address ?? '—' }}</dd>
                        <dt class="col-sm-4">Tax Number</dt>
                        <dd class="col-sm-8">{{ $vendor->tax_number ?? '—' }}</dd>
                        <dt class="col-sm-4">Payment Terms</dt>
                        <dd class="col-sm-8">{{ $vendor->payment_terms ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Purchase Orders</div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>PO Number</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendor->purchaseOrders as $po)
                                <tr>
                                    <td><a href="{{ route('purchase-orders.show', $po) }}">{{ $po->po_number }}</a></td>
                                    <td>{{ $po->order_date->format('Y-m-d') }}</td>
                                    <td><span class="badge bg-secondary">{{ str($po->status)->headline() }}</span></td>
                                    <td class="text-end">{{ number_format($po->totalCost(), 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No purchase orders yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            @can('recordPayment', $vendor)
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">Record Payment</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('vendors.payments.store', $vendor) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Amount</label>
                                <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm @error('amount') is-invalid @enderror" required>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Payment Date</label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="form-control form-control-sm @error('payment_date') is-invalid @enderror" required>
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Method</label>
                                <input type="text" name="method" class="form-control form-control-sm" placeholder="e.g. Bank Transfer, Cash">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Record Payment</button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Payment History</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($vendor->payments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                    <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No payments recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
