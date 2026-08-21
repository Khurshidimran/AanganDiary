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

    @push('scripts')
        <script>
            (function () {
                // The Orders tab's status pills, search box, and pagination
                // all stay inside this one container — intercept clicks/
                // submits on same-page links (pills, pagination) and fetch
                // just this tab's fragment instead of reloading the whole
                // page. Links to a different path (the "View" button, which
                // opens the attempt-history drill-down) are left alone.
                const tabOrders = document.getElementById('tab-orders');
                if (!tabOrders) {
                    return;
                }

                function loadFragment(url) {
                    tabOrders.style.opacity = '0.5';

                    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Request failed');
                            }

                            return response.text();
                        })
                        .then((html) => {
                            tabOrders.innerHTML = html;
                            tabOrders.style.opacity = '';
                            window.history.replaceState({}, '', url);
                        })
                        .catch(() => {
                            window.location.href = url;
                        });
                }

                tabOrders.addEventListener('click', function (e) {
                    const link = e.target.closest('a');
                    if (!link) {
                        return;
                    }

                    const url = new URL(link.href, window.location.origin);
                    if (url.pathname !== window.location.pathname) {
                        return; // different page (e.g. "View") — navigate normally
                    }

                    e.preventDefault();
                    loadFragment(link.href);
                });

                tabOrders.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const params = new URLSearchParams(new FormData(e.target));
                    loadFragment(window.location.pathname + '?' + params.toString());
                });
            })();
        </script>
    @endpush
@endsection
