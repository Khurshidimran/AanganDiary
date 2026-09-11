@extends('layouts.app')

@section('title', 'Profit & Loss')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Profit &amp; Loss</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.profit-and-loss.export.pdf', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf"></i> Export PDF
            </a>
            <a href="{{ route('reports.profit-and-loss.export.excel', request()->query()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>

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
                            <td>
                                <a href="#" class="pl-drill" data-src="{{ route('reports.ledger', ['account_id' => $row['account']->id, 'date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString(), 'embed' => 1]) }}" data-title="{{ $row['account']->code }} — {{ $row['account']->name }}">
                                    {{ $row['account']->code }} — {{ $row['account']->name }}
                                </a>
                            </td>
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
                            <td>
                                <a href="#" class="pl-drill" data-src="{{ route('reports.ledger', ['account_id' => $row['account']->id, 'date_from' => $dateFrom->toDateString(), 'date_to' => $dateTo->toDateString(), 'embed' => 1]) }}" data-title="{{ $row['account']->code }} — {{ $row['account']->name }}">
                                    {{ $row['account']->code }} — {{ $row['account']->name }}
                                </a>
                            </td>
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

    <div class="modal fade" id="drillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="drillModalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 75vh;">
                    <iframe id="drillModalFrame" src="" style="width: 100%; height: 100%; border: 0;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.pl-drill').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('drillModalTitle').textContent = link.dataset.title;
                document.getElementById('drillModalFrame').src = link.dataset.src;
                new bootstrap.Modal(document.getElementById('drillModal')).show();
            });
        });
        document.getElementById('drillModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('drillModalFrame').src = '';
        });
    </script>
@endsection
