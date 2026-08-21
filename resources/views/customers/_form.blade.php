<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $customer?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $customer?->phone) }}"
           class="form-control @error('phone') is-invalid @enderror" required>
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email (optional)</label>
    <input id="email" type="email" name="email" value="{{ old('email', $customer?->email) }}"
           class="form-control @error('email') is-invalid @enderror">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="account_id" class="form-label">Linked Ledger Account (optional)</label>
    <select id="account_id" name="account_id" class="form-select @error('account_id') is-invalid @enderror">
        <option value="">Use the shared Accounts Receivable account</option>
        @foreach ($accounts as $account)
            <option value="{{ $account->id }}" @selected((string) old('account_id', $customer?->account_id) === (string) $account->id)>
                {{ $account->code }} — {{ $account->name }}
            </option>
        @endforeach
    </select>
    <div class="form-text">Only needed for a specific credit/wholesale customer who should get their own distinct ledger line.</div>
    @error('account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="notes" class="form-label">Notes (optional)</label>
    <textarea id="notes" name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $customer?->notes) }}</textarea>
    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
