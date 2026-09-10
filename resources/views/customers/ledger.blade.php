@extends('layouts.app')

@section('title', $customer->name.' — Ledger')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">{{ $customer->name }} — Ledger</h1>
            <div class="text-muted small">{{ $customer->phone }}{{ $customer->email ? ' · '.$customer->email : '' }}</div>
        </div>
        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> All Customers
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('customers.ledger', $customer) }}" class="row g-2 align-items-end">
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
                        <a href="{{ route('customers.ledger', $customer) }}" class="btn btn-sm btn-link">All Time</a>
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
                    <div class="fs-3 fw-bold">Rs. {{ number_format($opening, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-1">Closing Balance <span class="text-muted">(what they currently owe)</span></div>
                    <div class="fs-3 fw-bold {{ $closing > 0.009 ? 'text-danger' : '' }}">Rs. {{ number_format($closing, 2) }}</div>
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
                        <th>Description</th>
                        <th class="text-end">Charged</th>
                        <th class="text-end">Settled</th>
                        <th class="text-end">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filteredRows as $row)
                        <tr>
                            <td class="text-nowrap">{{ $row['date']->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('orders.show', $row['order']) }}" class="text-decoration-none">{{ $row['description'] }}</a>
                                <div class="text-muted small">{{ $row['subtitle'] }}</div>
                            </td>
                            <td class="text-end text-danger">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '—' }}</td>
                            <td class="text-end text-success">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '—' }}</td>
                            <td class="text-end fw-semibold {{ $row['balance'] > 0.009 ? 'text-danger' : '' }}">Rs. {{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No order activity in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
