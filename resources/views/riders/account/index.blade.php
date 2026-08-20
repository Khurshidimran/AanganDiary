@extends('layouts.app')

@section('title', $rider->user->name.' — Rider Account')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-0">Rider Account</h1>
            <div class="text-muted small">Delivery performance, cash settlement and earnings</div>
        </div>
        <a href="{{ route('riders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> All Riders
        </a>
    </div>

    @include('riders.account.partials._header')

    <form method="GET" class="d-flex align-items-end gap-2 mb-3 flex-wrap">
        <div>
            <label class="form-label small text-muted mb-1">Period</label>
            <select name="period" class="form-select form-select-sm" onchange="
                document.getElementById('custom-range').classList.toggle('d-none', this.value !== 'custom');
                if (this.value !== 'custom') { this.form.submit(); }
            ">
                <option value="today" @selected($preset === 'today')>Today</option>
                <option value="yesterday" @selected($preset === 'yesterday')>Yesterday</option>
                <option value="this_week" @selected($preset === 'this_week')>This Week</option>
                <option value="this_month" @selected($preset === 'this_month')>This Month</option>
                <option value="custom" @selected($preset === 'custom')>Custom Date Range</option>
                <option value="all_time" @selected($preset === 'all_time')>All Time</option>
            </select>
        </div>
        <div id="custom-range" class="d-flex gap-2 align-items-end {{ $preset === 'custom' ? '' : 'd-none' }}">
            <div>
                <label class="form-label small text-muted mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from?->format('Y-m-d') }}">
            </div>
            <div>
                <label class="form-label small text-muted mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to?->format('Y-m-d') }}">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Apply</button>
        </div>
        <div class="ms-auto text-muted small align-self-center">
            <i class="bi bi-info-circle"></i> Cash &amp; earnings balances below are always current — not scoped to this period.
        </div>
    </form>

    @include('riders.account.partials._kpi-cards')
    @include('riders.account.partials._financial-summary')

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-orders-btn" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button" role="tab">
                Orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-cash-ledger-btn" data-bs-toggle="tab" data-bs-target="#tab-cash-ledger" type="button" role="tab">
                Cash Ledger
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-earnings-btn" data-bs-toggle="tab" data-bs-target="#tab-earnings" type="button" role="tab">
                Earnings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-trips-btn" data-bs-toggle="tab" data-bs-target="#tab-trips" type="button" role="tab">
                Trips
            </button>
        </li>
    </ul>
    <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white shadow-sm mb-4">
        <div class="tab-pane fade show active" id="tab-orders" role="tabpanel">
            @include('riders.account.partials._tab-orders')
        </div>
        <div class="tab-pane fade" id="tab-cash-ledger" role="tabpanel">
            @include('riders.account.partials._tab-cash-ledger')
        </div>
        <div class="tab-pane fade" id="tab-earnings" role="tabpanel">
            @include('riders.account.partials._tab-earnings')
        </div>
        <div class="tab-pane fade" id="tab-trips" role="tabpanel">
            @include('riders.account.partials._tab-trips')
        </div>
    </div>

    @can('rider_wallet.manage')
        @include('riders.account.partials._modal-cash-deposit')
        @include('riders.account.partials._modal-pay-rider')

        @if ($errors->any())
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        @if (old('deposit_date') !== null)
                            new bootstrap.Modal(document.getElementById('modal-cash-deposit')).show();
                        @elseif (old('payment_date') !== null)
                            new bootstrap.Modal(document.getElementById('modal-pay-rider')).show();
                        @endif
                    });
                </script>
            @endpush
        @endif
    @endcan
@endsection
