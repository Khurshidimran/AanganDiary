@extends('layouts.app')

@section('title', 'Dispatch Board')

@section('content')
    <h1 class="h4 mb-3">Dispatch Board</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Awaiting Assignment</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Address</th>
                        <th class="text-end">Total</th>
                        <th>Payment</th>
                        <th>Assign Rider</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($awaiting as $order)
                        <tr>
                            <td><a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a></td>
                            <td>{{ $order->customer_name ?? '—' }}</td>
                            <td>{{ $order->formattedAddress() ?? '—' }}</td>
                            <td class="text-end">{{ $order->currency }} {{ number_format($order->total, 2) }}</td>
                            <td>
                                <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ str($order->payment_status)->headline() }}
                                </span>
                            </td>
                            <td>
                                @can('dispatch.manage')
                                    <form method="POST" action="{{ route('dispatch.assign', $order) }}"
                                          class="d-flex flex-column gap-1" style="min-width: 240px;">
                                        @csrf
                                        <div class="d-flex gap-2">
                                            <select name="rider_id" class="form-select form-select-sm" required>
                                                <option value="">Select rider</option>
                                                @foreach ($riders as $rider)
                                                    <option value="{{ $rider->id }}">{{ $rider->user->name }} ({{ $rider->zone ?? 'no zone' }})</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-primary text-nowrap">Assign</button>
                                        </div>
                                        <input type="datetime-local" name="scheduled_dispatch_at"
                                               value="{{ now()->format('Y-m-d\TH:i') }}"
                                               class="form-control form-control-sm" title="Scheduled dispatch date/time (defaults to now — adjust if needed)">
                                        <input type="text" name="rider_instructions" maxlength="1000"
                                               class="form-control form-control-sm" placeholder="Instructions for rider (optional)">
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No confirmed orders awaiting assignment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('dispatch.manage')
        <form id="bulk-actions-form" method="POST">
            @csrf
        </form>
    @endcan

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-semibold">In Progress</span>
            @can('dispatch.manage')
                <div class="d-flex gap-2">
                    <button type="submit" form="bulk-actions-form" formaction="{{ route('dispatch.picked-up.bulk') }}"
                            class="btn btn-sm btn-outline-primary">
                        Mark Selected Picked Up
                    </button>
                    <button type="submit" form="bulk-actions-form" formaction="{{ route('dispatch.out-for-delivery.bulk') }}"
                            class="btn btn-sm btn-outline-primary">
                        Mark Selected Out for Delivery
                    </button>
                </div>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        @can('dispatch.manage')
                            <th><input type="checkbox" id="select-all-in-progress" class="form-check-input"></th>
                        @endcan
                        <th>Order</th>
                        <th>Rider</th>
                        <th>Delivery Status</th>
                        <th>COD</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inProgress as $order)
                        <tr>
                            @can('dispatch.manage')
                                <td>
                                    <input type="checkbox" form="bulk-actions-form" name="order_ids[]" value="{{ $order->id }}"
                                           class="form-check-input in-progress-checkbox">
                                </td>
                            @endcan
                            <td>
                                <a href="{{ route('orders.show', $order) }}">{{ $order->shopify_order_number ?? $order->shopify_order_id }}</a>
                                @if ($order->isCancelled())
                                    <span class="badge bg-danger ms-1">Cancelled in Shopify</span>
                                @endif
                                @if ($order->scheduled_dispatch_at)
                                    <div class="small text-muted"><i class="bi bi-clock"></i> {{ $order->scheduled_dispatch_at->format('Y-m-d h:i A') }}</div>
                                @endif
                                @if ($order->rider_instructions)
                                    <div class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $order->rider_instructions }}">
                                        <i class="bi bi-chat-left-text"></i> {{ $order->rider_instructions }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $order->rider?->user->name ?? '—' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ str($order->delivery_status)->headline() }}</span>
                            </td>
                            <td>{{ $order->cod_amount > 0 ? $order->currency.' '.number_format($order->cod_amount, 2) : '—' }}</td>
                            <td class="text-end">
                                @can('dispatch.manage')
                                    <div class="d-flex gap-1 justify-content-end flex-wrap">
                                        @if ($order->canBeMarkedPickedUp())
                                            <form method="POST" action="{{ route('dispatch.picked-up', $order) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Picked Up</button>
                                            </form>
                                        @endif
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
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" data-bs-target="#fail-modal-{{ $order->id }}">Failed</button>
                                            <form method="POST" action="{{ route('dispatch.returned', $order) }}"
                                                  onsubmit="return confirm('Mark returned? This releases the allocated stock back.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">Returned</button>
                                            </form>

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
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No deliveries in progress.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('dispatch.manage')
        @push('scripts')
            <script>
                document.getElementById('select-all-in-progress')?.addEventListener('change', function () {
                    document.querySelectorAll('.in-progress-checkbox').forEach((checkbox) => {
                        checkbox.checked = this.checked;
                    });
                });
            </script>
        @endpush
    @endcan
@endsection
