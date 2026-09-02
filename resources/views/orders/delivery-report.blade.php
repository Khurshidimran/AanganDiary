@extends('layouts.app')

@section('title', 'Delivery Report')

@section('content')
    <h1 class="h4 mb-3">Delivery Report</h1>
    <p class="text-muted small mb-3">How many orders — and what value — are actually getting delivered, seen through three different date lenses over the same period.</p>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('orders.delivery-report') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo->toDateString() }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-outline-secondary w-100">Filter</button>
                </div>
                @unless ($dateFrom->isSameDay(now()->startOfMonth()) && $dateTo->isToday())
                    <div class="col-md-2">
                        <a href="{{ route('orders.delivery-report') }}" class="btn btn-sm btn-link">This Month</a>
                    </div>
                @endunless
            </form>
        </div>
    </div>

    <div class="row g-3">
        @foreach (['order_date', 'dispatch_date', 'delivery_date'] as $key)
            @php $row = $rows[$key]; @endphp
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">{{ $row['label'] }}</div>
                    <div class="card-body">
                        <div class="text-muted small mb-3">{{ $row['hint'] }}</div>

                        @if ($key !== 'delivery_date')
                            <dl class="row mb-2 small">
                                <dt class="col-7">Total Orders</dt>
                                <dd class="col-5 text-end">{{ $row['total_count'] }} / {{ number_format($row['total_value'], 2) }}</dd>
                            </dl>
                            <hr class="my-2">
                        @endif

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted text-uppercase" style="font-size: .7rem;">Delivered</div>
                                <div class="small">Count / Value</div>
                            </div>
                            <div class="text-end">
                                <div class="fs-4 fw-bold text-success">{{ $row['delivered_count'] }}</div>
                                <div class="small text-muted">Rs. {{ number_format($row['delivered_value'], 2) }}</div>
                            </div>
                        </div>

                        @if ($key !== 'delivery_date' && $row['rate'] !== null)
                            <div class="mt-2 small text-muted">{{ $row['rate'] }}% delivered so far</div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
