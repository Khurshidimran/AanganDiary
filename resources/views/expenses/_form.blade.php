<div class="row">
    <div class="col-md-6 mb-3">
        <label for="expense_category_id" class="form-label">Category</label>
        <select id="expense_category_id" name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required>
            <option value="">Select a category</option>
            @foreach ($categories as $id => $name)
                <option value="{{ $id }}" @selected((string) old('expense_category_id', $expense?->expense_category_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('expense_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="warehouse_id" class="form-label">Warehouse (optional)</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($warehouses as $id => $name)
                <option value="{{ $id }}" @selected((string) old('warehouse_id', $expense?->warehouse_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="amount" class="form-label">Amount</label>
        <input id="amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense?->amount) }}"
               class="form-control @error('amount') is-invalid @enderror" required>
        @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="expense_date" class="form-label">Expense Date</label>
        <input id="expense_date" type="date" name="expense_date" value="{{ old('expense_date', $expense?->expense_date?->toDateString() ?? now()->toDateString()) }}"
               class="form-control @error('expense_date') is-invalid @enderror" required>
        @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="payment_method" class="form-label">Payment Method</label>
        <select id="payment_method" name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
            @foreach (['cash' => 'Cash', 'bank' => 'Bank', 'mobile' => 'Mobile'] as $value => $label)
                <option value="{{ $value }}" @selected(old('payment_method', $expense?->payment_method ?? 'cash') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="reference_number" class="form-label">Reference Number (optional)</label>
    <input id="reference_number" type="text" name="reference_number" value="{{ old('reference_number', $expense?->reference_number) }}"
           class="form-control @error('reference_number') is-invalid @enderror">
    @error('reference_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $expense?->notes) }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
