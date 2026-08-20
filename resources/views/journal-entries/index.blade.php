@extends('layouts.app')

@section('title', 'Journal Entries')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Journal Entries</h1>
        <div class="d-flex gap-2">
            @can('create', \App\Models\JournalEntry::class)
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        New Voucher
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('vouchers.create', 'cash-payment') }}">Cash Payment Voucher</a></li>
                        <li><a class="dropdown-item" href="{{ route('vouchers.create', 'cash-receipt') }}">Cash Receipt Voucher</a></li>
                        <li><a class="dropdown-item" href="{{ route('vouchers.create', 'bank-payment') }}">Bank Payment Voucher</a></li>
                        <li><a class="dropdown-item" href="{{ route('vouchers.create', 'bank-receipt') }}">Bank Receipt Voucher</a></li>
                    </ul>
                </div>
                <a href="{{ route('journal-entries.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Journal Voucher
                </a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('journal-entries.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach (['journal' => 'Journal', 'cash_payment' => 'Cash Payment', 'cash_receipt' => 'Cash Receipt', 'bank_payment' => 'Bank Payment', 'bank_receipt' => 'Bank Receipt'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Source</label>
                    <select name="source" class="form-select form-select-sm">
                        <option value="">All sources</option>
                        <option value="manual" @selected(request('source') === 'manual')>Manual</option>
                        <option value="system" @selected(request('source') === 'system')>System</option>
                        <option value="reversal" @selected(request('source') === 'reversal')>Reversal</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @if (request()->anyFilled(['type', 'source', 'date_from', 'date_to']))
                    <div class="col-md-1">
                        <a href="{{ route('journal-entries.index') }}" class="btn btn-sm btn-link">Clear</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Source</th>
                        <th>Reference</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr>
                            <td><a href="{{ route('journal-entries.show', $entry) }}">{{ $entry->entry_number }}</a></td>
                            <td>{{ $entry->entry_date->format('Y-m-d') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ str($entry->type)->headline() }}</span></td>
                            <td>
                                <span class="badge {{ match ($entry->source) {
                                    'system' => 'bg-info text-dark',
                                    'reversal' => 'bg-warning text-dark',
                                    default => 'bg-secondary',
                                } }}">
                                    {{ str($entry->source)->headline() }}
                                </span>
                            </td>
                            <td>{{ $entry->reference_type ? str($entry->reference_type)->headline() : '—' }}</td>
                            <td class="text-end">{{ number_format($entry->totalDebit(), 2) }}</td>
                            <td>
                                <span class="badge {{ $entry->status === 'voided' ? 'bg-danger' : 'bg-success' }}">
                                    {{ str($entry->status)->headline() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No journal entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $entries->links() }}
    </div>
@endsection
