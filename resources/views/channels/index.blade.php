@extends('layouts.app')

@section('title', 'Channels')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Channels</h1>
        @can('create', \App\Models\Channel::class)
            <a href="{{ route('channels.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> New Channel
            </a>
        @endcan
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th class="text-end">Orders</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($channels as $channel)
                        <tr>
                            <td>
                                {{ $channel->name }}
                                @if ($channel->is_system)
                                    <span class="badge bg-secondary ms-1" title="System-protected channel">System</span>
                                @endif
                            </td>
                            <td><code>{{ $channel->code }}</code></td>
                            <td class="text-end">{{ $channel->orders_count }}</td>
                            <td>
                                <span class="badge {{ $channel->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ str($channel->status)->headline() }}
                                </span>
                            </td>
                            <td class="text-end">
                                @can('update', $channel)
                                    <a href="{{ route('channels.edit', $channel) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                                @can('delete', $channel)
                                    @if (! $channel->is_system)
                                        <form action="{{ route('channels.destroy', $channel) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this channel?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No channels found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $channels->links() }}
    </div>
@endsection
