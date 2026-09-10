@extends('layouts.app')

@section('title', 'Trial Balance')

@php
    $drCrColor = fn (string $label) => $label === 'DR' ? 'text-primary' : 'text-danger';
    $openingBalanced = abs($totals['opening_debit'] - $totals['opening_credit']) < 0.01;
    $closingBalanced = abs($totals['closing_debit'] - $totals['closing_credit']) < 0.01;
@endphp

@section('content')
    <h1 class="h4 mb-3">Trial Balance</h1>
    <p class="text-muted small mb-3">Every account's balance before this period (Opening), what moved during it (Period), and where it ended up (Closing).</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.trial-balance') }}" class="row g-2 align-items-end">
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

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th rowspan="2" class="align-middle">Code</th>
                        <th rowspan="2" class="align-middle">Account</th>
                        <th colspan="2" class="text-center border-start">Opening</th>
                        <th colspan="2" class="text-center border-start">Period</th>
                        <th colspan="2" class="text-center border-start">Closing</th>
                    </tr>
                    <tr>
                        <th class="text-end border-start">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end border-start">Debit</th>
                        <th class="text-end">Credit</th>
                        <th class="text-end border-start">Debit</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['account']->code }}</td>
                            <td>{{ $row['account']->name }}</td>
                            <td class="text-end border-start {{ $row['opening']['label'] === 'DR' ? 'text-primary' : '' }}">
                                {{ $row['opening']['label'] === 'DR' && $row['opening']['amount'] > 0 ? number_format($row['opening']['amount'], 2) : '' }}
                            </td>
                            <td class="text-end {{ $row['opening']['label'] === 'CR' ? 'text-danger' : '' }}">
                                {{ $row['opening']['label'] === 'CR' && $row['opening']['amount'] > 0 ? number_format($row['opening']['amount'], 2) : '' }}
                            </td>
                            <td class="text-end border-start text-primary">{{ $row['period_debit'] > 0 ? number_format($row['period_debit'], 2) : '' }}</td>
                            <td class="text-end text-danger">{{ $row['period_credit'] > 0 ? number_format($row['period_credit'], 2) : '' }}</td>
                            <td class="text-end border-start {{ $row['closing']['label'] === 'DR' ? 'text-primary' : '' }}">
                                {{ $row['closing']['label'] === 'DR' && $row['closing']['amount'] > 0 ? number_format($row['closing']['amount'], 2) : '' }}
                            </td>
                            <td class="text-end {{ $row['closing']['label'] === 'CR' ? 'text-danger' : '' }}">
                                {{ $row['closing']['label'] === 'CR' && $row['closing']['amount'] > 0 ? number_format($row['closing']['amount'], 2) : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No posted activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold {{ $openingBalanced ? 'table-light' : 'table-danger' }}">
                        <td colspan="2" class="text-end">Totals</td>
                        <td class="text-end border-start text-primary">{{ number_format($totals['opening_debit'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['opening_credit'], 2) }}</td>
                        <td class="text-end border-start text-primary">{{ number_format($totals['period_debit'], 2) }}</td>
                        <td class="text-end text-danger">{{ number_format($totals['period_credit'], 2) }}</td>
                        <td class="text-end border-start text-primary {{ $closingBalanced ? '' : 'bg-danger text-white' }}">{{ number_format($totals['closing_debit'], 2) }}</td>
                        <td class="text-end text-danger {{ $closingBalanced ? '' : 'bg-danger text-white' }}">{{ number_format($totals['closing_credit'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @unless ($openingBalanced)
        <div class="alert alert-danger mt-3">Opening totals don't balance (Debit ≠ Credit) — this indicates a data integrity issue before this period even starts and should be investigated.</div>
    @endunless
    @unless ($closingBalanced)
        <div class="alert alert-danger mt-3">Closing totals don't balance (Debit ≠ Credit) — this indicates a data integrity issue and should be investigated.</div>
    @endunless
@endsection
