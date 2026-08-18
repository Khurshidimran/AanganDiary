@extends('layouts.app')

@section('title', 'Shopify Connected')

@section('content')
    <h1 class="h4 mb-3">Shopify Connection</h1>

    @if (! $accessToken)
        <div class="alert alert-danger">
            Shopify didn't return an access token. Try connecting again — if this keeps happening, double-check
            <code>SHOPIFY_API_KEY</code> / <code>SHOPIFY_API_SECRET</code> in <code>.env</code> and that this app's
            redirect URL is registered correctly in your Shopify app settings.
        </div>
    @else
        @if ($isOnline)
            <div class="alert alert-warning">
                <strong>This came back as an online (expiring) token</strong>, not offline — Shopify included an
                <code>expires_in</code> field. That shouldn't happen with this flow; double-check your app's
                configuration in Shopify before relying on this token long-term.
            </div>
        @else
            <div class="alert alert-success">
                Offline access token received — this one does not expire.
            </div>
        @endif

        <div class="card shadow-sm">
            <div class="card-body">
                <label class="form-label fw-semibold">New access token</label>
                <input type="text" class="form-control font-monospace mb-2" value="{{ $accessToken }}" readonly onclick="this.select()">
                <div class="small text-muted mb-3">
                    Copy this into <code>SHOPIFY_ACCESS_TOKEN</code> in your production <code>.env</code> file,
                    then redeploy / clear config cache (<code>php artisan config:clear</code>) so it takes effect.
                    This page is the only place this token is shown — Shopify won't display it again.
                </div>

                <label class="form-label fw-semibold">Granted scopes</label>
                <div class="small text-muted">{{ $scope ?? '—' }}</div>
            </div>
        </div>
    @endif

    <a href="{{ route('shopify.index') }}" class="btn btn-sm btn-outline-secondary mt-3">Back to Shopify Integration</a>
@endsection
