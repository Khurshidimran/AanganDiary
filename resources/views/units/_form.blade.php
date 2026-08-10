<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $unit?->name) }}"
           class="form-control @error('name') is-invalid @enderror" placeholder="e.g. Gram" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="short_code" class="form-label">Short Code</label>
    <input id="short_code" type="text" name="short_code" value="{{ old('short_code', $unit?->short_code) }}"
           class="form-control @error('short_code') is-invalid @enderror" placeholder="e.g. g" required>
    @error('short_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="type" class="form-label">Type</label>
    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror">
        @foreach (['mass' => 'Mass', 'volume' => 'Volume', 'count' => 'Count'] as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $unit?->type) === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Units only convert against others of the same type.</div>
</div>

<div class="mb-3">
    <label for="conversion_factor" class="form-label">Conversion Factor</label>
    <input id="conversion_factor" type="number" step="0.0001" min="0.0001"
           name="conversion_factor" value="{{ old('conversion_factor', $unit?->conversion_factor) }}"
           class="form-control @error('conversion_factor') is-invalid @enderror" required>
    @error('conversion_factor') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">
        How many of this type's base unit equal 1 of this unit — e.g. Gram = 1 (it is the mass base unit), Kilogram = 1000.
    </div>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $unit?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
