@php
    $typeLabels = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expenses'];
@endphp

<div class="col-md-6 mb-3">
    <label for="{{ $key }}" class="form-label">{{ $label }}</label>
    <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
        <option value="">None</option>
        @foreach ($grouped as $type => $accountsOfType)
            <optgroup label="{{ $typeLabels[$type] ?? ucfirst($type) }}">
                @foreach ($accountsOfType as $option)
                    <option value="{{ $option->id }}" @selected((string) $current === (string) $option->id)>{{ $option->code }} — {{ $option->name }}</option>
                @endforeach
            </optgroup>
        @endforeach
    </select>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
