@extends(request()->boolean('embed') ? 'layouts.embed' : 'layouts.app')

@section('title', 'Expenses')

@section('content')
    <h1 class="h5 mb-3">Expenses</h1>
    <div class="text-muted small mb-3">{{ $dateFrom->format('d M Y') }} &mdash; {{ $dateTo->format('d M Y') }}</div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Warehouse</th>
                        <th>Payment Method</th>
                        <th>Notes</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $expense)
                        <tr>
                            <td class="text-nowrap">{{ $expense->expense_date->format('d-M-Y') }}</td>
                            <td>{{ $expense->category?->name ?? '—' }}</td>
                            <td>{{ $expense->warehouse?->name ?? '—' }}</td>
                            <td class="text-muted small">{{ str($expense->payment_method)->headline() }}</td>
                            <td class="text-muted small">{{ $expense->notes ?? '—' }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No expenses in this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="5">Total</td>
                        <td class="text-end">{{ number_format($rows->sum('amount'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endsection
