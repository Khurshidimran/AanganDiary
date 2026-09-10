@extends('layouts.app')

@section('title', 'General Ledger')

@php
    $typeBadge = fn (string $type) => match ($type) {
        \App\Models\Account::TYPE_ASSET => 'bg-primary',
        \App\Models\Account::TYPE_LIABILITY => 'bg-danger',
        \App\Models\Account::TYPE_EQUITY => 'bg-dark',
        \App\Models\Account::TYPE_REVENUE => 'bg-success',
        \App\Models\Account::TYPE_EXPENSE => 'bg-warning text-dark',
        default => 'bg-secondary',
    };
    $drCrColor = fn (string $label) => $label === 'DR' ? 'text-primary' : 'text-danger';
@endphp

@section('content')
    <h1 class="h4 mb-3">General Ledger</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.ledger') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-0">Account</label>
                    <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach (['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'] as $type => $label)
                            @php $options = $accounts->where('type', $type); @endphp
                            @if ($options->isNotEmpty())
                                <optgroup label="{{ $label }}">
                                    @foreach ($options as $option)
                                        <option value="{{ $option->id }}" @selected($account && $account->id === $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Go</button>
                </div>
            </form>
        </div>
    </div>

    @if ($account)
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-2">Opening Balance</div>
                        <div class="fs-3 fw-bold {{ $drCrColor($openingBalance['label']) }}">
                            {{ number_format($openingBalance['amount'], 2) }}
                            <span class="fs-6 fw-semibold">{{ $openingBalance['label'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-2">Period Activity</div>
                        <div class="fs-5">
                            <span class="text-primary fw-semibold">DR {{ number_format($periodDebit, 2) }}</span>
                            <span class="text-muted mx-1">|</span>
                            <span class="text-danger fw-semibold">CR {{ number_format($periodCredit, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase mb-2">Closing Balance</div>
                        <div class="fs-3 fw-bold {{ $drCrColor($closingBalance['label']) }}">
                            {{ number_format($closingBalance['amount'], 2) }}
                            <span class="fs-6 fw-semibold">{{ $closingBalance['label'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $typeBadge($account->type) }}">{{ ucfirst($account->type) }}</span>
                    <span class="fw-semibold">{{ $account->name }}</span>
                    <span class="text-muted small">{{ $account->code }}</span>
                </div>
                <div class="text-muted small">{{ $dateFrom->format('d M Y') }} — {{ $dateTo->format('d M Y') }}</div>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Entry No</th>
                            <th>Reference</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td>{{ $dateFrom->copy()->subDay()->format('d M Y') }}</td>
                            <td class="fw-semibold">Opening Balance</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-end fw-semibold {{ $drCrColor($openingBalance['label']) }}">
                                {{ number_format($openingBalance['amount'], 2) }} <span class="small">{{ $openingBalance['label'] }}</span>
                            </td>
                        </tr>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row['entry']->entry_date->format('d M Y') }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $row['description'] }}</div>
                                    <div class="text-muted small">{{ $row['subtitle'] }}</div>
                                </td>
                                <td><a href="{{ route('journal-entries.show', $row['entry']) }}">{{ $row['entry']->entry_number }}</a></td>
                                <td class="text-muted small">{{ $row['reference'] ?? '—' }}</td>
                                <td class="text-end text-primary">{{ $row['line']->debit > 0 ? number_format($row['line']->debit, 2) : '—' }}</td>
                                <td class="text-end text-success">{{ $row['line']->credit > 0 ? number_format($row['line']->credit, 2) : '—' }}</td>
                                <td class="text-end fw-semibold {{ $drCrColor($row['balance']['label']) }}">
                                    {{ number_format($row['balance']['amount'], 2) }} <span class="small">{{ $row['balance']['label'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No activity in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="4" class="fw-semibold">Totals</td>
                            <td class="text-end fw-semibold text-primary">{{ number_format($periodDebit, 2) }}</td>
                            <td class="text-end fw-semibold text-success">{{ number_format($periodCredit, 2) }}</td>
                            <td class="text-end fw-semibold {{ $drCrColor($closingBalance['label']) }}">
                                {{ number_format($closingBalance['amount'], 2) }} <span class="small">{{ $closingBalance['label'] }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@endsection
