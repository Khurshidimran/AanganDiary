@canany(['dispatch.view', 'riders.view'])
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Today's Delivery Operations</span>
            <a href="{{ route('monitoring.index') }}" class="small">View full Live Monitoring <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="card-body">
            @can('dispatch.view')
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center h-100">
                            <div class="fs-4 fw-bold">{{ $todaysOperations['dispatched_today'] }}</div>
                            <div class="small text-muted">Dispatched (in progress)</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center h-100">
                            <div class="fs-4 fw-bold">{{ $todaysOperations['pending_pickup_today'] }}</div>
                            <div class="small text-muted">Pending Pickup</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center h-100">
                            <div class="fs-4 fw-bold text-success">{{ $todaysOperations['completed_today'] }}</div>
                            <div class="small text-muted">Completed</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3 text-center h-100 {{ $todaysOperations['failed_today'] > 0 ? 'border-danger' : '' }}">
                            <div class="fs-4 fw-bold {{ $todaysOperations['failed_today'] > 0 ? 'text-danger' : '' }}">{{ $todaysOperations['failed_today'] }}</div>
                            <div class="small text-muted">Failed Attempts</div>
                        </div>
                    </div>
                </div>
            @endcan

            <div class="row g-3">
                @can('dispatch.view')
                    <div class="col-lg-7">
                        <div class="fw-semibold small mb-2">Delivery Windows</div>
                        @forelse ($todaysOperations['windows'] as $window)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ $window['label'] }}</span>
                                <span class="text-muted">
                                    {{ $window['done'] }}/{{ $window['total'] }} done
                                    @if ($window['avg_minutes'] !== null)
                                        · {{ $window['avg_minutes'] }} min/stop avg
                                    @endif
                                </span>
                            </div>
                            <div class="progress mb-3" style="height: 6px;">
                                <div class="progress-bar bg-info" style="width: {{ $window['total'] > 0 ? round($window['done'] / $window['total'] * 100) : 0 }}%"></div>
                            </div>
                        @empty
                            <div class="text-muted small">Nothing scheduled today.</div>
                        @endforelse
                    </div>
                @endcan

                @can('riders.view')
                    <div class="col-lg-5">
                        <div class="fw-semibold small mb-2">Live Riders ({{ $liveRiders->count() }})</div>
                        <ul class="list-group list-group-flush">
                            @forelse ($liveRiders as $rider)
                                @php
                                    $statusLabel = $rider->operationalStatusLabel();
                                    $badgeColor = match ($statusLabel) {
                                        'On Delivery' => 'bg-primary',
                                        'Checked In' => 'bg-success',
                                        'Online' => 'bg-info text-dark',
                                        default => 'bg-secondary',
                                    };
                                    $stopCount = $rider->orders->count();
                                @endphp
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <div class="small fw-semibold">{{ $rider->user->name }}</div>
                                        <div class="text-muted small">{{ $rider->zone ?? '—' }} · {{ $stopCount }} stop{{ $stopCount === 1 ? '' : 's' }}</div>
                                    </div>
                                    <span class="badge {{ $badgeColor }}">{{ $statusLabel }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted small px-0">No riders currently checked in or online.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endcanany
