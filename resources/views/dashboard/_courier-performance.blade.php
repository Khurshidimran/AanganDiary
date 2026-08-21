@can('rider_wallet.view')
    <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Courier Performance</div>
        <div class="card-body">
            @forelse ($courierPerformance as $row)
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span class="fw-semibold">{{ $row['rider']->user->name }}</span>
                    <span class="text-muted">{{ $row['first_attempt_rate'] !== null ? $row['first_attempt_rate'].'% first-attempt' : '—' }}</span>
                </div>
                <div class="progress mb-1" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: {{ $row['first_attempt_rate'] ?? 0 }}%"></div>
                </div>
                <div class="text-muted small mb-3">
                    {{ $row['delivered'] }} delivered
                    · Rs. {{ number_format($row['cod_collected'], 0) }} COD collected
                    @if ($row['avg_hours'] !== null)
                        · {{ $row['avg_hours'] }}h avg to deliver
                    @endif
                </div>
            @empty
                <div class="text-muted small">No deliveries in this period yet.</div>
            @endforelse
        </div>
    </div>
@endcan
