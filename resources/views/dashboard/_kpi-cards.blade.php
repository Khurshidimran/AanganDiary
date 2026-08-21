@php
    $deltaBadge = function (?float $delta) {
        if ($delta === null) {
            return '';
        }

        $color = $delta >= 0 ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis';
        $sign = $delta >= 0 ? '+' : '';

        return '<span class="badge '.$color.'">'.$sign.$delta.'%</span>';
    };
@endphp
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small">Total Orders</div>
                    {!! $deltaBadge($kpi['total_orders_delta']) !!}
                </div>
                <div class="fs-4 fw-bold">{{ number_format($kpi['total_orders']) }}</div>
                <div class="text-muted small">vs. previous period</div>
                <div class="progress mt-2" style="height: 4px;"><div class="progress-bar bg-primary" style="width: 100%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small">Awaiting Fulfillment</div>
                    @if ($kpi['awaiting_fulfillment_delta'] !== 0)
                        <span class="badge {{ $kpi['awaiting_fulfillment_delta'] > 0 ? 'bg-secondary-subtle text-secondary-emphasis' : 'bg-success-subtle text-success-emphasis' }}">
                            {{ $kpi['awaiting_fulfillment_delta'] > 0 ? '+' : '' }}{{ $kpi['awaiting_fulfillment_delta'] }}
                        </span>
                    @endif
                </div>
                <div class="fs-4 fw-bold">{{ number_format($kpi['awaiting_fulfillment']) }}</div>
                <div class="text-muted small">{{ $kpi['awaiting_past_schedule'] }} past scheduled dispatch</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-secondary" style="width: {{ $kpi['total_orders'] > 0 ? min(100, round($kpi['awaiting_fulfillment'] / $kpi['total_orders'] * 100)) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Out for Delivery</div>
                <div class="fs-4 fw-bold text-info">{{ number_format($kpi['out_for_delivery']) }}</div>
                <div class="text-muted small">{{ $kpi['riders_active'] }} rider{{ $kpi['riders_active'] === 1 ? '' : 's' }} active</div>
                <div class="progress mt-2" style="height: 4px;"><div class="progress-bar bg-info" style="width: 100%"></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small">Delivered</div>
                    {!! $deltaBadge($kpi['delivered_delta']) !!}
                </div>
                <div class="fs-4 fw-bold text-success">{{ number_format($kpi['delivered']) }}</div>
                <div class="text-muted small">
                    {{ $kpi['first_attempt_rate'] !== null ? $kpi['first_attempt_rate'].'% first-attempt success' : 'No deliveries yet' }}
                </div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-success" style="width: {{ $kpi['first_attempt_rate'] ?? 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="text-muted small">Delayed / Exceptions</div>
                    @if ($kpi['exceptions_delta'] !== 0)
                        <span class="badge {{ $kpi['exceptions_delta'] > 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis' }}">
                            {{ $kpi['exceptions_delta'] > 0 ? '+' : '' }}{{ $kpi['exceptions_delta'] }}
                        </span>
                    @endif
                </div>
                <div class="fs-4 fw-bold text-danger">{{ number_format($kpi['exceptions']) }}</div>
                <div class="text-muted small">{{ $kpi['exceptions_unresolved'] }} still unresolved</div>
                <div class="progress mt-2" style="height: 4px;">
                    <div class="progress-bar bg-danger" style="width: {{ $kpi['total_orders'] > 0 ? min(100, round($kpi['exceptions'] / $kpi['total_orders'] * 100)) : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Revenue @can('rider_wallet.view') / COD Due @endcan</div>
                <div class="fs-4 fw-bold">Rs. {{ number_format($kpi['revenue'], 0) }}</div>
                @can('rider_wallet.view')
                    <div class="text-muted small">Rs. {{ number_format($kpi['cod_due'], 0) }} pending rider settlement</div>
                @endcan
                <div class="progress mt-2" style="height: 4px;"><div class="progress-bar bg-primary" style="width: 100%"></div></div>
            </div>
        </div>
    </div>
</div>
