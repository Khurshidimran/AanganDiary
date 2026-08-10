<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $brand?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $brand?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
