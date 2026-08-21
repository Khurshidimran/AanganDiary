@php $address = $address ?? null; @endphp
<div class="mb-2">
    <label class="form-label small">Label (optional)</label>
    <input type="text" name="label" value="{{ old('label', $address?->label) }}" class="form-control form-control-sm" placeholder="Home, Office, etc.">
</div>
<div class="mb-2">
    <label class="form-label small">Address Line 1</label>
    <input type="text" name="address1" value="{{ old('address1', $address?->address1) }}" class="form-control form-control-sm" required>
</div>
<div class="mb-2">
    <label class="form-label small">Address Line 2 (optional)</label>
    <input type="text" name="address2" value="{{ old('address2', $address?->address2) }}" class="form-control form-control-sm">
</div>
<div class="row g-2 mb-2">
    <div class="col-6">
        <label class="form-label small">City</label>
        <select name="city" class="form-select form-select-sm" required>
            @foreach (['Lahore'] as $cityOption)
                <option value="{{ $cityOption }}" @selected(old('city', $address?->city) === $cityOption)>{{ $cityOption }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label class="form-label small">Country</label>
        <input type="text" name="country" value="{{ old('country', $address?->country ?? 'Pakistan') }}" class="form-control form-control-sm" required>
    </div>
</div>
<div class="mb-2">
    <label class="form-label small">Phone (optional override)</label>
    <input type="text" name="phone" value="{{ old('phone', $address?->phone) }}" class="form-control form-control-sm">
</div>
