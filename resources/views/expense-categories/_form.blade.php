<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $expenseCategory?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $expenseCategory?->status ?? 'active') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="chart_account_id" class="form-label">GL Account (optional)</label>
    <select id="chart_account_id" name="chart_account_id" class="form-select @error('chart_account_id') is-invalid @enderror">
        <option value="">Use the Default Expense Account from Account Mapping</option>
        @foreach ($accounts as $option)
            <option value="{{ $option->id }}" @selected((string) old('chart_account_id', $expenseCategory?->chart_account_id) === (string) $option->id)>{{ $option->code }} — {{ $option->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Expenses in this category post to this account instead of the general default.</div>
    @error('chart_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
