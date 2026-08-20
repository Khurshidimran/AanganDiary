@extends('layouts.app')

@section('title', 'Dispatch Board')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Dispatch Board</h1>
            <p class="text-muted small mb-0">Internal tool — track order status, rider load, and assign pending orders.</p>
        </div>
        <form method="GET" action="{{ route('dispatch.index') }}" class="d-flex align-items-end gap-2">
            <div>
                <label class="form-label small mb-0">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="form-control form-control-sm">
            </div>
            <div>
                <label class="form-label small mb-0">To</label>
                <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="form-control form-control-sm">
            </div>
            <button type="submit" class="btn btn-sm btn-outline-secondary">Filter</button>
            @unless ($dateFrom->isToday() && $dateTo->isToday())
                <a href="{{ route('dispatch.index') }}" class="btn btn-sm btn-link">Today</a>
            @endunless
        </form>
    </div>
    <hr class="mb-4">

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="text-uppercase text-muted small fw-semibold mb-3" style="letter-spacing: .05em;">Status Summary</div>
            <div class="row g-3 text-center">
                <div class="col-6 col-md">
                    <div class="border rounded p-3 h-100">
                        <div class="h4 mb-0">{{ $statusCounts['pending'] }}</div>
                        <div class="text-muted small">Pending</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="border rounded p-3 h-100">
                        <div class="h4 mb-0">{{ $statusCounts['assigned'] }}</div>
                        <div class="text-muted small">Assigned</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="border rounded p-3 h-100">
                        <div class="h4 mb-0">{{ $statusCounts['picked_up'] }}</div>
                        <div class="text-muted small">Pickup</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="border rounded p-3 h-100">
                        <div class="h4 mb-0">{{ $statusCounts['out_for_delivery'] }}</div>
                        <div class="text-muted small">Out for delivery</div>
                    </div>
                </div>
                <div class="col-6 col-md">
                    <div class="border rounded p-3 h-100">
                        <div class="h4 mb-0">{{ $statusCounts['delivered'] }}</div>
                        <div class="text-muted small">Delivered</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('dispatch.manage')
        <form id="bulk-assign-form" method="POST" action="{{ route('dispatch.assign.bulk') }}">
            @csrf
        </form>
    @endcan

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h2 class="h6 mb-0">Orders ({{ $orders->count() }})</h2>
                    @unless ($dateFrom->isToday() && $dateTo->isToday())
                        <div class="text-muted small">
                            {{ $dateFrom->isSameDay($dateTo) ? $dateFrom->format('M j, Y') : $dateFrom->format('M j, Y').' – '.$dateTo->format('M j, Y') }}
                        </div>
                    @endunless
                </div>
                <div class="d-flex gap-1 flex-wrap" id="status-filter">
                    <button type="button" class="btn btn-sm btn-dark filter-pill" data-filter="all">All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-filter="pending">Pending</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-filter="assigned">Assigned</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-filter="picked_up">Pickup</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-filter="out_for_delivery">Out for delivery</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-filter="delivered">Delivered</button>
                </div>
            </div>

            @can('dispatch.manage')
                <div class="card shadow-sm mb-3">
                    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
                        <div class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="select-all-pending">
                            <label class="form-check-label small" for="select-all-pending">Select all pending</label>
                        </div>
                        <span class="text-muted small" id="selected-count">0 selected</span>
                        <select form="bulk-assign-form" name="rider_id" id="bulk-rider-select" class="form-select form-select-sm ms-auto" style="max-width: 240px;">
                            <option value="">Select rider for bulk assignment...</option>
                            @foreach ($riders as $rider)
                                <option value="{{ $rider->id }}">{{ $rider->user->name }} ({{ $rider->zone ?? 'no zone' }})</option>
                            @endforeach
                        </select>
                        <button type="submit" form="bulk-assign-form" class="btn btn-sm btn-primary" id="bulk-assign-btn" disabled>
                            Assign Selected
                        </button>
                    </div>
                </div>
            @endcan

            <div id="orders-list" class="d-flex flex-column gap-3">
                @forelse ($orders as $order)
                    @php
                        $bucket = in_array($order->delivery_status, ['pending', 'failed']) ? 'pending' : $order->delivery_status;
                        $badge = match ($order->delivery_status) {
                            'pending' => ['bg-danger-subtle text-danger-emphasis border border-danger-subtle', 'Pending'],
                            'failed' => ['bg-danger text-white', 'Failed'],
                            'assigned' => ['bg-secondary-subtle text-secondary-emphasis border', 'Assigned'],
                            'picked_up' => ['bg-info-subtle text-info-emphasis border border-info-subtle', 'Pickup'],
                            'out_for_delivery' => ['bg-primary-subtle text-primary-emphasis border border-primary-subtle', 'Out for delivery'],
                            'delivered' => ['bg-success-subtle text-success-emphasis border border-success-subtle', 'Delivered'],
                            default => ['bg-light text-dark border', str($order->delivery_status)->headline()],
                        };
                        $itemCount = $order->items->count();
                        $pickupLocation = $order->rider?->warehouse?->name ?? $defaultWarehouse?->name;
                    @endphp
                    <div class="card shadow-sm order-card" data-status="{{ $bucket }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                                <div class="fw-semibold d-flex align-items-center gap-2">
                                    @can('dispatch.manage')
                                        @if ($order->canBeAssigned() && ! $order->isCancelled())
                                            <input type="checkbox" form="bulk-assign-form" name="order_ids[]" value="{{ $order->id }}" class="form-check-input bulk-select-checkbox mt-0">
                                        @endif
                                    @endcan
                                    <a href="{{ route('orders.show', $order) }}" class="text-reset text-decoration-none">
                                        #{{ $order->shopify_order_number ?? $order->shopify_order_id }} · {{ $order->customer_name ?? 'Unknown' }}
                                    </a>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    @if ($order->isCancelled())
                                        <span class="badge bg-danger">Cancelled in Shopify</span>
                                    @endif
                                    <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                                </div>
                            </div>

                            <div class="d-flex gap-3 text-muted small mb-2">
                                <span><i class="bi bi-clock"></i> {{ $order->shopify_created_at?->format('h:i A') }}</span>
                                <span><i class="bi bi-box-seam"></i> {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</span>
                            </div>

                            @if ($pickupLocation)
                                <div class="small text-muted mb-1"><i class="bi bi-shop"></i> Pickup: {{ $pickupLocation }}</div>
                            @endif
                            <div class="small text-muted mb-3"><i class="bi bi-geo-alt"></i> Drop-off: {{ $order->formattedAddress() ?? '—' }}</div>

                            @if ($order->delivery_status === 'failed' && $order->delivery_failure_reason)
                                <div class="alert alert-danger py-1 px-2 small mb-2">Failed attempt: {{ $order->delivery_failure_reason }}</div>
                            @endif
                            @if ($order->rider_instructions)
                                <div class="small text-muted mb-2"><i class="bi bi-chat-left-text"></i> {{ $order->rider_instructions }}</div>
                            @endif

                            @can('dispatch.manage')
                                @if ($order->canBeAssigned() && ! $order->isCancelled())
                                    <form method="POST" action="{{ route('dispatch.assign', $order) }}">
                                        @csrf
                                        <select name="rider_id" class="form-select form-select-sm" required onchange="this.form.requestSubmit()">
                                            <option value="">Assign a rider...</option>
                                            @foreach ($riders as $rider)
                                                <option value="{{ $rider->id }}" @selected($order->rider_id === $rider->id)>
                                                    {{ $rider->user->name }} ({{ $rider->zone ?? 'no zone' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="button" class="btn btn-sm btn-link p-0 mt-1" data-bs-toggle="collapse" data-bs-target="#opts-{{ $order->id }}">
                                            More options
                                        </button>
                                        <div class="collapse mt-2" id="opts-{{ $order->id }}">
                                            <input type="text" name="rider_instructions" maxlength="1000" class="form-control form-control-sm mb-2" placeholder="Instructions for rider (optional)">
                                            <input type="datetime-local" name="scheduled_dispatch_at" class="form-control form-control-sm" title="Scheduled dispatch date/time (defaults to now)">
                                        </div>
                                    </form>
                                @elseif ($order->delivery_status === 'assigned')
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <span class="small"><i class="bi bi-person"></i> {{ $order->rider?->user->name ?? '—' }}</span>
                                        <div class="d-flex gap-1 align-items-center">
                                            <span class="text-muted small text-uppercase me-1" style="font-size: .7rem;">Update status:</span>
                                            @if ($order->canBeMarkedPickedUp())
                                                <form method="POST" action="{{ route('dispatch.picked-up', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Picked Up</button>
                                                </form>
                                            @endif
                                            @if ($order->canBeUnassigned())
                                                <form method="POST" action="{{ route('dispatch.unassign', $order) }}" onsubmit="return confirm('Unassign this rider? The order goes back to pending.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none">Unassign</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @elseif (in_array($order->delivery_status, ['picked_up', 'out_for_delivery']))
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <span class="small"><i class="bi bi-person"></i> {{ $order->rider?->user->name ?? '—' }}</span>
                                        <div class="d-flex gap-1 flex-wrap justify-content-end align-items-center">
                                            <span class="text-muted small text-uppercase me-1" style="font-size: .7rem;">Update status:</span>
                                            @if ($order->canBeMarkedOutForDelivery())
                                                <form method="POST" action="{{ route('dispatch.out-for-delivery', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Out for Delivery</button>
                                                </form>
                                            @endif
                                            @if ($order->canBeMarkedDelivered())
                                                <form method="POST" action="{{ route('dispatch.delivered', $order) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">Delivered</button>
                                                </form>
                                            @endif
                                            @if ($order->canBeMarkedFailedOrReturned())
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#fail-modal-{{ $order->id }}">Failed</button>
                                                <form method="POST" action="{{ route('dispatch.returned', $order) }}" onsubmit="return confirm('Mark returned? This releases the allocated stock back.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Returned</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @elseif ($order->delivery_status === 'delivered')
                                    <div class="small text-muted">
                                        <i class="bi bi-check-circle text-success"></i>
                                        Delivered by {{ $order->rider?->user->name ?? '—' }}
                                        @if ($order->delivered_at) at {{ $order->delivered_at->format('h:i A') }} @endif
                                    </div>
                                @endif

                                @if ($order->canBeMarkedFailedOrReturned())
                                    <div class="modal fade" id="fail-modal-{{ $order->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST" action="{{ route('dispatch.failed', $order) }}" class="modal-content">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Mark Delivery Failed</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <label class="form-label">Reason</label>
                                                    <input type="text" name="reason" class="form-control" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-danger">Mark Failed</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="card shadow-sm">
                        <div class="text-center text-muted py-5">Nothing on the board right now.</div>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            @php
                $available = $riders->filter(fn ($r) => $r->orders->count() === 0)->values();
            @endphp

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white fw-semibold">Available Riders ({{ $available->count() }})</div>
                <ul class="list-group list-group-flush">
                    @forelse ($available as $rider)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><span class="text-success">●</span> {{ $rider->user->name }}</span>
                            <span class="text-muted small text-end">
                                <i class="bi bi-bicycle"></i> {{ str($rider->vehicle_type)->headline() }} · {{ $rider->zone ?? '—' }}
                            </span>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No riders currently available.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Rider Workload</div>
                <ul class="list-group list-group-flush">
                    @forelse ($riders as $rider)
                        @php $activeCount = $rider->orders->count(); @endphp
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><span class="{{ $activeCount > 0 ? 'text-primary' : 'text-success' }}">●</span> {{ $rider->user->name }}</span>
                                <span class="badge {{ $activeCount > 0 ? 'bg-primary-subtle text-primary-emphasis border border-primary-subtle' : 'bg-light text-dark border' }}">
                                    {{ $activeCount > 0 ? 'On delivery' : 'Available' }}
                                </span>
                            </div>
                            <div class="text-muted small mt-1">
                                <i class="bi bi-bicycle"></i> {{ str($rider->vehicle_type)->headline() }} · {{ $rider->zone ?? '—' }} · {{ $activeCount }} active
                            </div>
                            @foreach ($rider->orders as $activeOrder)
                                <div class="d-flex justify-content-between align-items-center small mt-1">
                                    <span class="text-truncate" style="max-width: 65%;">
                                        #{{ $activeOrder->shopify_order_number ?? $activeOrder->shopify_order_id }} · {{ $activeOrder->formattedAddress() ?? '—' }}
                                    </span>
                                    <span class="badge bg-light text-dark border">{{ str($activeOrder->delivery_status)->headline() }}</span>
                                </div>
                            @endforeach
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">No riders currently checked in.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const buttons = document.querySelectorAll('#status-filter .filter-pill');
                const cards = document.querySelectorAll('#orders-list .order-card');

                function applyFilter(filter) {
                    cards.forEach((card) => {
                        card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
                    });
                    buttons.forEach((btn) => {
                        const isActive = btn.dataset.filter === filter;
                        btn.classList.toggle('btn-dark', isActive);
                        btn.classList.toggle('btn-outline-secondary', !isActive);
                    });
                }

                buttons.forEach((btn) => btn.addEventListener('click', () => applyFilter(btn.dataset.filter)));
                applyFilter('all');
            })();

            (function () {
                const selectAll = document.getElementById('select-all-pending');
                const riderSelect = document.getElementById('bulk-rider-select');
                const assignBtn = document.getElementById('bulk-assign-btn');
                if (!selectAll || !riderSelect || !assignBtn) {
                    return;
                }

                function checkboxes() {
                    return document.querySelectorAll('.bulk-select-checkbox');
                }

                function updateBulkUI() {
                    const checked = document.querySelectorAll('.bulk-select-checkbox:checked').length;
                    document.getElementById('selected-count').textContent = checked + ' selected';
                    assignBtn.disabled = checked === 0 || !riderSelect.value;
                    selectAll.checked = checked > 0 && checked === checkboxes().length;
                }

                selectAll.addEventListener('change', function () {
                    checkboxes().forEach((cb) => { cb.checked = this.checked; });
                    updateBulkUI();
                });

                riderSelect.addEventListener('change', updateBulkUI);
                document.getElementById('orders-list').addEventListener('change', function (e) {
                    if (e.target.classList.contains('bulk-select-checkbox')) {
                        updateBulkUI();
                    }
                });

                updateBulkUI();
            })();
        </script>
    @endpush
@endsection
