@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
    <h1 class="h4 mb-3">Balance Sheet</h1>
    <p class="text-muted small mb-3">As of a single point in time — what the business owns, owes, and has invested, plus profit earned so far that hasn't formally been closed into equity.</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.balance-sheet') }}" class="row g-2 align-items-end">
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

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Assets</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <tbody>
                            @forelse ($assets as $row)
                                <tr>
                                    <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No asset balances.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold table-light">
                                <td>Total Assets</td>
                                <td class="text-end">{{ number_format($totalAssets, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Liabilities</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <tbody>
                            @forelse ($liabilities as $row)
                                <tr>
                                    <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No liability balances.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold table-light">
                                <td>Total Liabilities</td>
                                <td class="text-end">{{ number_format($totalLiabilities, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Equity</div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <tbody>
                            @forelse ($equity as $row)
                                <tr>
                                    <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center text-muted py-3">No recorded equity balances.</td></tr>
                            @endforelse
                            <tr>
                                <td>
                                    Current Earnings <span class="text-muted small">(unposted — revenue minus expense to date)</span>
                                </td>
                                <td class="text-end {{ $currentEarnings < 0 ? 'text-danger' : '' }}">{{ number_format($currentEarnings, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="fw-semibold table-light">
                                <td>Total Equity</td>
                                <td class="text-end">{{ number_format($totalEquity, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="small text-muted">Total Assets vs. Total Liabilities + Equity</span>
            <span class="fw-semibold">
                {{ number_format($totalAssets, 2) }} = {{ number_format($totalLiabilities + $totalEquity, 2) }}
            </span>
        </div>
    </div>

    @unless ($isBalanced)
        <div class="alert alert-danger mt-3">Assets do not equal Liabilities + Equity — this indicates a data integrity issue and should be investigated.</div>
    @endunless
@endsection
