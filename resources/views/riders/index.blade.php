@extends('layouts.app')

@section('title', 'Riders')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Riders</h1>
        @can('create', \App\Models\RiderProfile::class)
            <a href="{{ route('riders.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Rider
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Vehicle</th>
                        <th>Zone</th>
                        <th>Warehouse</th>
                        <th>Last Seen</th>
                        <th class="text-end">Wallet Balance</th>
                        <th>Status</th>
                        <th>Check-In</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($riders as $rider)
                        <tr>
                            <td>{{ $rider->user->name }}</td>
                            <td>{{ $rider->phone }}</td>
                            <td>{{ ucfirst($rider->vehicle_type) }}{{ $rider->vehicle_number ? " ({$rider->vehicle_number})" : '' }}</td>
                            <td>{{ $rider->zone ?? '—' }}</td>
                            <td>{{ $rider->warehouse?->name ?? '—' }}</td>
                            <td>
                                @if ($rider->last_location_at)
                                    <a href="https://maps.google.com/?q={{ $rider->last_latitude }},{{ $rider->last_longitude }}" target="_blank" class="small">
                                        {{ $rider->last_location_at->diffForHumans() }}
                                    </a>
                                @else
                                    <span class="text-muted small">Never</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('riders.wallet', $rider) }}">{{ number_format($rider->wallet_balance, 2) }}</a>
                            </td>
                            <td>
                                <span class="badge {{ match ($rider->status) {
                                    'active' => 'bg-success',
                                    'on_leave' => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                } }}">
                                    {{ str($rider->status)->headline() }}
                                </span>
                            </td>
                            <td>
                                @if ($rider->is_checked_in)
                                    <span class="badge bg-success">Checked In</span>
                                @else
                                    <span class="badge bg-secondary">Not Checked In</span>
                                @endif
                                @can('update', $rider)
                                    <form method="POST"
                                          action="{{ route($rider->is_checked_in ? 'riders.check-out' : 'riders.check-in', $rider) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-link p-0 ms-1">
                                            {{ $rider->is_checked_in ? 'Check Out' : 'Check In' }}
                                        </button>
                                    </form>
                                @endcan
                            </td>
                            <td class="text-end">
                                @can('dispatch.view')
                                    <a href="{{ route('riders.manifest', $rider) }}" class="btn btn-sm btn-outline-secondary" title="Download delivery manifest">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                @endcan
                                @can('update', $rider)
                                    <a href="{{ route('riders.edit', $rider) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $rider)
                                    <form action="{{ route('riders.destroy', $rider) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this rider?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No riders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $riders->links() }}
    </div>
@endsection
