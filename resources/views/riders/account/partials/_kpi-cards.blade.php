@php
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
    Delivery Performance &middot; {{ $periodLabel }}
</div>
<div class="row g-2 mb-4">
    @foreach ([
        ['Total Orders', $stats['total_orders'], 'text-dark'],
        ['Assigned', $stats['assigned'], 'text-secondary'],
        ['Picked Up', $stats['picked_up'], 'text-info'],
        ['Out for Delivery', $stats['out_for_delivery'], 'text-primary'],
        ['Delivered', $stats['delivered'], 'text-success'],
        ['Failed', $stats['failed'], 'text-danger'],
        ['Returned', $stats['returned'], 'text-warning'],
    ] as [$label, $value, $color])
        <div class="col-6 col-md-3 col-lg">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-3">
                    <div class="fs-4 fw-bold {{ $color }}">{{ $value }}</div>
                    <div class="small text-muted">{{ $label }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
