@extends('layouts.app')

@section('title', 'Daily Report')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Daily Report</h1>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('reports.daily') }}" class="row g-2 align-items-end">
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
                @unless ($dateFrom->isToday() && $dateTo->isToday())
                    <div class="col-md-2">
                        <a href="{{ route('reports.daily') }}" class="btn btn-sm btn-link">Today</a>
                    </div>
                @endunless
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm h-100 daily-stat" data-type="delivered" role="button">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Delivered</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($deliveredValue, 2) }}</div>
                    <div class="text-muted small">{{ $deliveredCount }} order(s) &mdash; click for detail</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 daily-stat" data-type="returned" role="button">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Returned</div>
                    <div class="fs-4 fw-bold text-danger">{{ number_format($returnedValue, 2) }}</div>
                    <div class="text-muted small">{{ $returnedCount }} order(s) &mdash; click for detail</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100 daily-stat" data-type="expenses" role="button">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Expenses</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($expenseValue, 2) }}</div>
                    <div class="text-muted small">{{ $expenseCount }} entr{{ $expenseCount === 1 ? 'y' : 'ies' }} &mdash; click for detail</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-2">Net (Delivered &minus; Expenses)</div>
                    <div class="fs-4 fw-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($net, 2) }}</div>
                    <div class="text-muted small">&nbsp;</div>
                </div>
            </div>
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
        document.querySelectorAll('.daily-stat').forEach(function (card) {
            card.addEventListener('click', function () {
                var type = card.dataset.type;
                var titles = {delivered: 'Delivered Orders', returned: 'Returned Orders', expenses: 'Expenses'};
                var params = new URLSearchParams({
                    type: type,
                    date_from: '{{ $dateFrom->toDateString() }}',
                    date_to: '{{ $dateTo->toDateString() }}',
                    embed: 1,
                });
                document.getElementById('drillModalTitle').textContent = titles[type] || 'Detail';
                document.getElementById('drillModalFrame').src = '{{ route('reports.daily.detail') }}?' + params.toString();
                new bootstrap.Modal(document.getElementById('drillModal')).show();
            });
        });
        document.getElementById('drillModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('drillModalFrame').src = '';
        });
    </script>
@endsection
