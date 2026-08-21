<div class="card shadow-sm h-100">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-semibold">Orders Trend</span>
            <div class="text-muted small">Last 7 days — orders vs. delivered vs. exceptions</div>
        </div>
    </div>
    <div class="card-body">
        <canvas id="orders-trend-chart" height="90"></canvas>
    </div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        (function () {
            const ctx = document.getElementById('orders-trend-chart');
            if (!ctx || typeof Chart === 'undefined') {
                return;
            }

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($trend['labels']),
                    datasets: [
                        {
                            label: 'Orders',
                            data: @json($trend['orders']),
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Delivered',
                            data: @json($trend['delivered']),
                            borderColor: '#198754',
                            backgroundColor: 'rgba(25, 135, 84, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Exceptions',
                            data: @json($trend['exceptions']),
                            borderColor: '#dc3545',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            tension: 0.3,
                            fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    interaction: {mode: 'index', intersect: false},
                    plugins: {legend: {position: 'bottom'}},
                    scales: {y: {beginAtZero: true}},
                },
            });
        })();
    </script>
@endpush
