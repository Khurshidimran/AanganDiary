@extends('layouts.app')

@section('title', 'Profit & Loss')

@section('content')
    <h1 class="h4 mb-3">Profit &amp; Loss</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.profit-and-loss') }}" class="row g-2 align-items-end">
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

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Revenue</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <tbody>
                    @forelse ($revenueAccounts as $row)
                        <tr>
                            <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                            <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">No revenue in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold table-light">
                        <td>Total Revenue</td>
                        <td class="text-end">{{ number_format($totalRevenue, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Expenses</div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <tbody>
                    @forelse ($expenseAccounts as $row)
                        <tr>
                            <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                            <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted py-3">No expenses in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold table-light">
                        <td>Total Expenses</td>
                        <td class="text-end">{{ number_format($totalExpense, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="h6 mb-0">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</span>
            <span class="h5 mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format(abs($netProfit), 2) }}</span>
        </div>
    </div>
@endsection
