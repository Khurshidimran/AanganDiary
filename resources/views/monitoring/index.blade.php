@extends('layouts.app')

@section('title', 'Live Monitoring')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Live Monitoring</h1>
        <span class="small text-muted">
            <i class="bi bi-arrow-repeat"></i> Refreshes every 30s — last updated <span id="monitoring-last-updated">{{ now()->format('h:i:s A') }}</span>
        </span>
    </div>

    <div id="monitoring-content">
        @include('monitoring._content')
    </div>

    @push('scripts')
        <script>
            (function () {
                const content = document.getElementById('monitoring-content');
                const lastUpdated = document.getElementById('monitoring-last-updated');
                const url = window.location.href;

                function refresh() {
                    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error('Request failed');
                            }

                            return response.text();
                        })
                        .then((html) => {
                            // Only the stats/tables fragment is replaced — the
                            // page itself never navigates, so scroll position
                            // (and anything else on screen) stays put instead
                            // of jumping back to the top every 30 seconds.
                            content.innerHTML = html;
                            lastUpdated.textContent = new Date().toLocaleTimeString('en-US');
                        })
                        .catch(() => {
                            // A transient network hiccup shouldn't interrupt
                            // the polling loop — just try again next tick.
                        });
                }

                setInterval(refresh, 30000);
            })();
        </script>
    @endpush
@endsection
