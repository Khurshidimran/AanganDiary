<div class="card shadow-sm h-100">
    <div class="card-header bg-white fw-semibold">Delivery Funnel</div>
    <div class="card-body">
        @foreach ($funnel['stages'] as $stage)
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ $stage['label'] }}</span>
                <span class="text-muted">{{ number_format($stage['count']) }} · {{ $stage['percent'] }}%</span>
            </div>
            <div class="progress mb-3" style="height: 8px;">
                <div class="progress-bar {{ $loop->last ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $stage['percent'] }}%"></div>
            </div>
        @endforeach
        <div class="text-muted small border-top pt-2 mt-1">
            Delivered rate {{ $funnel['delivered_rate'] }}% · RTO rate {{ $funnel['rto_rate'] }}%
        </div>
    </div>
</div>
