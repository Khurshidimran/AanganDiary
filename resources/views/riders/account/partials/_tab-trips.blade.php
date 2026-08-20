<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr>
                    <th>Trip #</th>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Checkout</th>
                    <th class="text-end">Orders</th>
                    <th class="text-end">Delivered</th>
                    <th class="text-end">Returned</th>
                    <th>Trip Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trips as $trip)
                    <tr>
                        <td>Trip #{{ str_pad($trips->firstItem() + $loop->index, 3, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $trip->checked_in_at->format('d M Y') }}</td>
                        <td>{{ $trip->checked_in_at->format('h:i A') }}</td>
                        <td>{{ $trip->checked_out_at?->format('h:i A') ?? '—' }}</td>
                        <td class="text-end">{{ $trip->orders_count }}</td>
                        <td class="text-end">{{ $trip->delivered_count }}</td>
                        <td class="text-end">{{ $trip->returned_count }}</td>
                        <td>
                            <span class="badge {{ $trip->status === 'active' ? 'bg-primary' : 'bg-success' }}">
                                {{ $trip->status === 'active' ? 'Active' : 'Completed' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('riders.wallet.trip', [$rider, $trip]) }}" class="btn btn-sm btn-outline-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No trips recorded yet — this rider hasn't checked in through the trip-tracked flow.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $trips->links() }}</div>
