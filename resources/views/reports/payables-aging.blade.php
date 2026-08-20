@extends('layouts.app')

@section('title', 'Payables Aging')

@section('content')
    <h1 class="h4 mb-3">Accounts Payable Aging</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.payables-aging') }}" class="row g-2 align-items-end">
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
                        <th>Vendor</th>
                        <th class="text-end">0–30 Days</th>
                        <th class="text-end">31–60 Days</th>
                        <th class="text-end">61–90 Days</th>
                        <th class="text-end">90+ Days</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td><a href="{{ route('vendors.show', $row['vendor']) }}">{{ $row['vendor']->name }}</a></td>
                            <td class="text-end">{{ number_format($row['current'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['days_31_60'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['days_61_90'], 2) }}</td>
                            <td class="text-end {{ $row['over_90'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['over_90'], 2) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($row['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No outstanding payables.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="fw-semibold table-light">
                        <td>Totals</td>
                        <td class="text-end">{{ number_format($totals['current'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['days_31_60'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['days_61_90'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['over_90'], 2) }}</td>
                        <td class="text-end">{{ number_format($totals['total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
