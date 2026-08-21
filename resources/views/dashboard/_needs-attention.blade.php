@php
    $visibleItems = collect($needsAttention)->filter(fn ($item) => auth()->user()->can($item['permission']));
@endphp
@if ($visibleItems->isNotEmpty())
    <div class="card shadow-sm border-danger mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-exclamation-triangle text-danger"></i> Needs Attention</span>
            <span class="badge bg-danger-subtle text-danger-emphasis">{{ $visibleItems->count() }} open</span>
        </div>
        <ul class="list-group list-group-flush">
            @foreach ($visibleItems as $item)
                <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                    <span class="small">{{ $item['message'] }}</span>
                    <a href="{{ $item['route'] }}" class="btn btn-sm btn-outline-danger text-nowrap">{{ $item['action'] }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
