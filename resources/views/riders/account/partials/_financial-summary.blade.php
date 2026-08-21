@php
    $net = (float) $financials['net_position'];
    $periodLabel = match ($preset) {
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'custom' => 'Custom Range',
        'all_time' => 'All Time',
        default => 'Today',
    };
@endphp
<div class="text-muted text-uppercase mb-2" style="font-size: .75rem; letter-spacing: .03em;">
    Financial Position &middot; {{ $periodLabel }}
</div>
@if ($preset !== 'all_time')
    <div class="alert alert-light border small py-2 px-3 mb-3">
        <i class="bi bi-info-circle"></i> Showing activity for <strong>{{ $periodLabel }}</strong> — an older
        unsettled balance won't appear here. Switch the period to <strong>All Time</strong> to see the true
        current outstanding balance.
    </div>
@endif
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-danger-subtle">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-cash-stack text-danger"></i> Cash to Aangan
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-7">COD Collected</dt>
                    <dd class="col-5 text-end">Rs. {{ number_format($financials['cod_collected'], 2) }}</dd>
                    <dt class="col-7">Cash Deposited</dt>
                    <dd class="col-5 text-end">Rs. {{ number_format($financials['cash_deposited'], 2) }}</dd>
                </dl>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted text-uppercase" style="font-size: .7rem;">Rider Owes Aangan</div>
                        <div class="small">Cash to Hand In</div>
                    </div>
                    <div class="fs-4 fw-bold text-danger">Rs. {{ number_format($financials['cash_to_hand_in'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm h-100 border-primary-subtle">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-wallet2 text-primary"></i> Delivery Earnings
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-7">Delivery Charges Earned</dt>
                    <dd class="col-5 text-end">Rs. {{ number_format($financials['earnings_earned'], 2) }}</dd>
                    <dt class="col-7">Paid to Rider</dt>
                    <dd class="col-5 text-end">Rs. {{ number_format($financials['earnings_paid'], 2) }}</dd>
                </dl>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted text-uppercase" style="font-size: .7rem;">Aangan Owes Rider</div>
                        <div class="small">Earnings Payable</div>
                    </div>
                    <div class="fs-4 fw-bold text-primary">Rs. {{ number_format($financials['earnings_payable'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 {{ $net > 0 ? 'border-danger-subtle' : ($net < 0 ? 'border-primary-subtle' : '') }}">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="text-muted text-uppercase" style="font-size: .7rem;">Net Position</div>
            <div class="fw-semibold">
                @if ($net > 0)
                    {{ $preset === 'all_time' ? 'Rider currently owes' : 'Rider owes' }} Aangan Rs. {{ number_format($net, 2) }}
                    @if ($preset !== 'all_time') for {{ $periodLabel }} @endif
                @elseif ($net < 0)
                    Aangan {{ $preset === 'all_time' ? 'currently owes' : 'owes' }} Rider Rs. {{ number_format(abs($net), 2) }}
                    @if ($preset !== 'all_time') for {{ $periodLabel }} @endif
                @else
                    Fully settled for {{ $periodLabel }} — no balance either way.
                @endif
            </div>
        </div>
        <div class="fs-3 fw-bold {{ $net > 0 ? 'text-danger' : ($net < 0 ? 'text-primary' : 'text-success') }}">
            Rs. {{ number_format(abs($net), 2) }}
        </div>
    </div>
</div>
