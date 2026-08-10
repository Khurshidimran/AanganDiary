<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $vendor?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="company_name" class="form-label">Company Name</label>
        <input id="company_name" type="text" name="company_name" value="{{ old('company_name', $vendor?->company_name) }}"
               class="form-control @error('company_name') is-invalid @enderror">
        @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="contact_person" class="form-label">Contact Person</label>
        <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $vendor?->contact_person) }}"
               class="form-control @error('contact_person') is-invalid @enderror">
        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $vendor?->phone) }}"
               class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $vendor?->email) }}"
               class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="address" class="form-label">Address</label>
    <textarea id="address" name="address" rows="2"
              class="form-control @error('address') is-invalid @enderror">{{ old('address', $vendor?->address) }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="tax_number" class="form-label">Tax Number</label>
        <input id="tax_number" type="text" name="tax_number" value="{{ old('tax_number', $vendor?->tax_number) }}"
               class="form-control @error('tax_number') is-invalid @enderror">
        @error('tax_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="payment_terms" class="form-label">Payment Terms</label>
        <input id="payment_terms" type="text" name="payment_terms" value="{{ old('payment_terms', $vendor?->payment_terms) }}"
               class="form-control @error('payment_terms') is-invalid @enderror" placeholder="e.g. Net 30">
        @error('payment_terms') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label for="opening_balance" class="form-label">Opening Balance</label>
        <input id="opening_balance" type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $vendor?->opening_balance ?? 0) }}"
               class="form-control @error('opening_balance') is-invalid @enderror">
        @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $vendor?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes</label>
    <textarea id="notes" name="notes" rows="2"
              class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $vendor?->notes) }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
