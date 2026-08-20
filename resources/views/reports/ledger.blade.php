@extends('layouts.app')

@section('title', 'General Ledger')

@section('content')
    <h1 class="h4 mb-3">General Ledger</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.ledger') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-0">Account</label>
                    <select name="account_id" class="form-select form-select-sm">
                        @foreach ($accounts as $option)
                            <option value="{{ $option->id }}" @selected($account && $account->id === $option->id)>{{ $option->code }} — {{ $option->name }}</option>
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
        <div class="mb-2 text-muted small">
            {{ $account->code }} — {{ $account->name }} ({{ str($account->normalBalance())->headline() }}-normal)
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Entry #</th>
                            <th>Narration</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-light">
                            <td colspan="5" class="fw-semibold">Opening Balance</td>
                            <td class="text-end fw-semibold">{{ number_format($openingBalance, 2) }}</td>
                        </tr>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['entry']->entry_date->format('Y-m-d') }}</td>
                                <td><a href="{{ route('journal-entries.show', $row['entry']) }}">{{ $row['entry']->entry_number }}</a></td>
                                <td>{{ $row['entry']->narration }}</td>
                                <td class="text-end">{{ $row['line']->debit > 0 ? number_format($row['line']->debit, 2) : '' }}</td>
                                <td class="text-end">{{ $row['line']->credit > 0 ? number_format($row['line']->credit, 2) : '' }}</td>
                                <td class="text-end">{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No activity in this period.</td>
                            </tr>
                        @endforelse
                        <tr class="table-light">
                            <td colspan="5" class="fw-semibold">Closing Balance</td>
                            <td class="text-end fw-semibold">{{ number_format($closingBalance, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
