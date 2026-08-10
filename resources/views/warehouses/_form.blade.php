<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $warehouse?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="code" class="form-label">Code</label>
    <input id="code" type="text" name="code" value="{{ old('code', $warehouse?->code) }}"
           class="form-control @error('code') is-invalid @enderror" required>
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Address</label>
    <textarea id="address" name="address" rows="2"
              class="form-control @error('address') is-invalid @enderror">{{ old('address', $warehouse?->address) }}</textarea>
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="contact_person" class="form-label">Contact Person</label>
        <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $warehouse?->contact_person) }}"
               class="form-control @error('contact_person') is-invalid @enderror">
        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $warehouse?->phone) }}"
               class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="latitude" class="form-label">Latitude</label>
        <input id="latitude" type="text" name="latitude" value="{{ old('latitude', $warehouse?->latitude) }}"
               class="form-control @error('latitude') is-invalid @enderror">
        @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label for="longitude" class="form-label">Longitude</label>
        <input id="longitude" type="text" name="longitude" value="{{ old('longitude', $warehouse?->longitude) }}"
               class="form-control @error('longitude') is-invalid @enderror">
        @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label for="shopify_location_id" class="form-label">Shopify Location ID (optional)</label>
    <input id="shopify_location_id" type="text" name="shopify_location_id" value="{{ old('shopify_location_id', $warehouse?->shopify_location_id) }}"
           class="form-control @error('shopify_location_id') is-invalid @enderror" placeholder="e.g. 61234567890">
    @error('shopify_location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Maps this warehouse to a Shopify location so its stock can be pushed to that location's inventory.</div>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $warehouse?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
