@php
    $badge = match ($status) {
        'pending' => ['bg-danger-subtle text-danger-emphasis border border-danger-subtle', 'Pending'],
        'failed' => ['bg-danger text-white', 'Failed'],
        'assigned' => ['bg-secondary-subtle text-secondary-emphasis border', 'Assigned'],
        'picked_up' => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'Picked Up'],
        'out_for_delivery' => ['bg-primary-subtle text-primary-emphasis border border-primary-subtle', 'Out for Delivery'],
        'delivered' => ['bg-success-subtle text-success-emphasis border border-success-subtle', 'Delivered'],
        'returned' => ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', 'Returned'],
        default => ['bg-light text-dark border', str($status)->headline()],
    };
@endphp
<span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
