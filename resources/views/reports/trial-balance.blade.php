@extends('layouts.app')

@section('title', 'Trial Balance')

@section('content')
    <h1 class="h4 mb-3">Trial Balance</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.trial-balance') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">As Of</label>
                    <input type="date" name="as_of" value="{{ $asOf->toDateString() }}" class="form-control form-control-sm">
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
                        <th>Code</th>
                        <th>Account</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['account']->code }}</td>
                            <td>{{ $row['account']->name }}</td>
                            <td class="text-end">{{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '' }}</td>
                            <td class="text-end">{{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No posted activity yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold {{ abs($totalDebit - $totalCredit) > 0.01 ? 'table-danger' : 'table-light' }}">
                        <td colspan="2" class="text-end">Totals</td>
                        <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                        <td class="text-end">{{ number_format($totalCredit, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if (abs($totalDebit - $totalCredit) > 0.01)
        <div class="alert alert-danger mt-3">Trial balance does not balance — this indicates a data integrity issue and should be investigated.</div>
    @endif
@endsection
