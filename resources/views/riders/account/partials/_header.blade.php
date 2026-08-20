@php
    $statusLabel = $rider->operationalStatusLabel();
    $statusColor = match ($statusLabel) {
        'On Delivery' => 'bg-primary',
        'Checked In' => 'bg-success',
        'Online' => 'bg-info text-dark',
        'Inactive' => 'bg-secondary',
        default => 'bg-light text-dark border',
    };
    $initials = collect(preg_split('/\s+/', trim($rider->user->name)))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');
@endphp
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="d-flex gap-3 align-items-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-semibold flex-shrink-0"
                     style="width: 56px; height: 56px; font-size: 1.15rem;">
                    {{ strtoupper($initials) ?: '?' }}
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h5 mb-0">{{ $rider->user->name }}</h1>
                        <span class="badge {{ $statusColor }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="text-muted small mt-1">
                        <i class="bi bi-telephone"></i> {{ $rider->phone }}
                        &middot; Rider ID: {{ strtoupper(substr($rider->id, 0, 8)) }}
                        @if ($rider->vehicle_number)
                            &middot; <i class="bi bi-scooter"></i> {{ $rider->vehicle_number }}
                        @endif
                        &middot; Last active:
                        {{ $rider->last_location_at?->diffForHumans() ?? $rider->checked_in_at?->diffForHumans() ?? 'Never' }}
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('rider_wallet.manage')
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-cash-deposit">
                        <i class="bi bi-cash-coin"></i> Record Cash Deposit
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-pay-rider">
                        <i class="bi bi-wallet2"></i> Pay Rider
                    </button>
                @endcan
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        More
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        @can('update', $rider)
                            <li><a class="dropdown-item" href="{{ route('riders.edit', $rider) }}">View Rider Profile</a></li>
                        @endcan
                        <li>
                            <a class="dropdown-item" href="#tab-cash-ledger" data-bs-toggle="tab" data-bs-target="#tab-cash-ledger" role="tab">
                                View Full History
                            </a>
                        </li>
                        @can('update', $rider)
                            @if ($rider->status !== \App\Models\RiderProfile::STATUS_INACTIVE)
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('riders.deactivate', $rider) }}"
                                          onsubmit="return confirm('Deactivate {{ $rider->user->name }}? They will no longer be assignable on the Dispatch Board.');">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">Deactivate Rider</button>
                                    </form>
                                </li>
                            @endif
                        @endcan
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
