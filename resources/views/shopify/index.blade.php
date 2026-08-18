@extends('layouts.app')

@section('title', 'Shopify Integration')

@section('content')
    <h1 class="h4 mb-3">Shopify Integration</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Connection Status</div>
                    @if ($isConfigured)
                        <div class="fs-5 fw-bold text-success">
                            <i class="bi bi-check-circle"></i> Connected
                        </div>
                        <div class="small text-muted">{{ $shopDomain }}</div>
                    @else
                        <div class="fs-5 fw-bold text-secondary">
                            <i class="bi bi-dash-circle"></i> Not Configured
                        </div>
                        <div class="small text-muted">
                            Set <code>SHOPIFY_SHOP_DOMAIN</code> and <code>SHOPIFY_ACCESS_TOKEN</code> in <code>.env</code> to connect.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-body d-flex flex-column gap-2">
                    <div class="text-muted small mb-1">Manual Sync</div>
                    @can('shopify.sync')
                        <form method="POST" action="{{ route('shopify.sync-products') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" {{ $isConfigured ? '' : 'disabled' }}>
                                <i class="bi bi-arrow-repeat"></i> Sync Products from Shopify
                            </button>
                        </form>
                        <form method="POST" action="{{ route('shopify.push-inventory') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" {{ $isConfigured ? '' : 'disabled' }}>
                                <i class="bi bi-cloud-upload"></i> Push Inventory to Shopify
                            </button>
                        </form>
                        <hr class="my-1">
                        <form method="POST" action="{{ route('shopify.sync-orders') }}" class="d-flex gap-2">
                            @csrf
                            <input type="datetime-local" name="since" class="form-control form-control-sm"
                                   value="{{ now()->subDay()->format('Y-m-d\TH:i') }}">
                            <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap" {{ $isConfigured ? '' : 'disabled' }}>
                                <i class="bi bi-arrow-repeat"></i> Sync Orders
                            </button>
                        </form>
                        <div class="small text-muted">
                            Pulls any order created or updated since the date/time above — use this to catch up after downtime. Safe to run repeatedly: existing orders are updated in place, never duplicated.
                        </div>
                        <hr class="my-1">
                        <a href="{{ route('shopify.connect') }}" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-key"></i> Connect Shopify (Get Offline Token)
                        </a>
                        <div class="small text-muted">
                            Use this if your access token was issued as an expiring "online" token (Shopify's <code>expires_in</code> field) — this requests a proper offline token instead, which never expires. Requires <code>SHOPIFY_API_KEY</code>/<code>SHOPIFY_API_SECRET</code> in <code>.env</code> and this app's redirect URL registered in your Shopify app settings.
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Sync History</div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Processed</th>
                        <th class="text-end">Created</th>
                        <th class="text-end">Updated</th>
                        <th class="text-end">Failed</th>
                        <th>Started</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($syncLogs as $log)
                        <tr>
                            <td>{{ str($log->sync_type)->headline() }}</td>
                            <td>
                                <span class="badge {{ match ($log->status) {
                                    'completed' => 'bg-success',
                                    'failed' => 'bg-danger',
                                    default => 'bg-secondary',
                                } }}">
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                            <td class="text-end">{{ $log->items_processed }}</td>
                            <td class="text-end">{{ $log->items_created }}</td>
                            <td class="text-end">{{ $log->items_updated }}</td>
                            <td class="text-end">{{ $log->items_failed }}</td>
                            <td>{{ $log->started_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $log->triggeredBy?->name ?? 'CLI' }}</td>
                        </tr>
                        @if ($log->error_summary)
                            <tr>
                                <td colspan="8" class="small text-danger">{{ $log->error_summary }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No syncs have run yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $syncLogs->links() }}
    </div>
@endsection
