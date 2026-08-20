<div class="row">
    <div class="col-md-4 mb-3">
        <label for="code" class="form-label">Code</label>
        <input id="code" type="text" name="code" value="{{ old('code', $account?->code) }}"
               class="form-control @error('code') is-invalid @enderror" required {{ $account?->is_system ? 'readonly' : '' }}>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-8 mb-3">
        <label for="name" class="form-label">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $account?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="type" class="form-label">Type</label>
        <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" {{ $account?->is_system ? 'disabled' : '' }}>
            @foreach (['asset' => 'Asset', 'liability' => 'Liability', 'equity' => 'Equity', 'revenue' => 'Revenue', 'expense' => 'Expense'] as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $account?->type ?? 'asset') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($account?->is_system)
            <input type="hidden" name="type" value="{{ $account->type }}">
            <div class="form-text">System accounts can't change type.</div>
        @endif
        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="parent_id" class="form-label">Parent Account</label>
        <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($parents as $id => $label)
                <option value="{{ $id }}" @selected((string) old('parent_id', $account?->parent_id) === (string) $id)>{{ $label }}</option>
            @endforeach
        </select>
        @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $account?->status ?? 'active') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
