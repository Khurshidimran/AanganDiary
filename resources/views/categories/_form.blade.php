<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $category?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="parent_id" class="form-label">Parent Category</label>
    <select id="parent_id" name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
        <option value="">None</option>
        @foreach ($parents as $id => $name)
            <option value="{{ $id }}" @selected((string) old('parent_id', $category?->parent_id) === (string) $id)>{{ $name }}</option>
        @endforeach
    </select>
    @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $category?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
