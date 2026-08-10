@php
    $u = $rider?->user;
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="name" class="form-label">Name</label>
        <input id="name" type="text" name="name" value="{{ old('name', $u?->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $u?->email) }}"
               class="form-control @error('email') is-invalid @enderror" required>
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="password" class="form-label">Password{{ $rider ? ' (leave blank to keep unchanged)' : '' }}</label>
        <input id="password" type="password" name="password"
               class="form-control @error('password') is-invalid @enderror" {{ $rider ? '' : 'required' }}>
        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" class="form-control">
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label for="phone" class="form-label">Phone</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $rider?->phone) }}"
               class="form-control @error('phone') is-invalid @enderror" required>
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="cnic" class="form-label">CNIC</label>
        <input id="cnic" type="text" name="cnic" value="{{ old('cnic', $rider?->cnic) }}"
               class="form-control @error('cnic') is-invalid @enderror">
        @error('cnic') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="vehicle_type" class="form-label">Vehicle Type</label>
        <select id="vehicle_type" name="vehicle_type" class="form-select @error('vehicle_type') is-invalid @enderror">
            @foreach (['bike' => 'Bike', 'van' => 'Van', 'bicycle' => 'Bicycle', 'car' => 'Car'] as $value => $label)
                <option value="{{ $value }}" @selected(old('vehicle_type', $rider?->vehicle_type ?? 'bike') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('vehicle_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="vehicle_number" class="form-label">Vehicle Number</label>
        <input id="vehicle_number" type="text" name="vehicle_number" value="{{ old('vehicle_number', $rider?->vehicle_number) }}"
               class="form-control @error('vehicle_number') is-invalid @enderror">
        @error('vehicle_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="zone" class="form-label">Zone</label>
        <input id="zone" type="text" name="zone" value="{{ old('zone', $rider?->zone) }}"
               class="form-control @error('zone') is-invalid @enderror" placeholder="e.g. Gulberg">
        @error('zone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="warehouse_id" class="form-label">Home Warehouse</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
            <option value="">None</option>
            @foreach ($warehouses as $id => $name)
                <option value="{{ $id }}" @selected((string) old('warehouse_id', $rider?->warehouse_id) === (string) $id)>{{ $name }}</option>
            @endforeach
        </select>
        @error('warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="per_delivery_rate" class="form-label">Per-Delivery Rate</label>
        <input id="per_delivery_rate" type="number" step="0.01" min="0" name="per_delivery_rate"
               value="{{ old('per_delivery_rate', $rider?->per_delivery_rate ?? 0) }}"
               class="form-control @error('per_delivery_rate') is-invalid @enderror" required>
        @error('per_delivery_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach (['active' => 'Active', 'inactive' => 'Inactive', 'on_leave' => 'On Leave'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $rider?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@if ($rider)
    <div class="mb-3">
        <label class="form-label">Wallet Balance</label>
        <div>
            <span class="badge {{ $rider->wallet_balance > 0 ? 'bg-warning text-dark' : ($rider->wallet_balance < 0 ? 'bg-info text-dark' : 'bg-secondary') }}">
                {{ number_format($rider->wallet_balance, 2) }}
            </span>
            <a href="{{ route('riders.wallet', $rider) }}" class="ms-2 small">View wallet ledger</a>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Last Known Location</label>
        <div>
            @if ($rider->last_location_at)
                <a href="https://maps.google.com/?q={{ $rider->last_latitude }},{{ $rider->last_longitude }}" target="_blank">
                    {{ $rider->last_latitude }}, {{ $rider->last_longitude }}
                </a>
                <span class="text-muted small">(as of {{ $rider->last_location_at->diffForHumans() }})</span>
            @else
                <span class="text-muted">No location pings received yet.</span>
            @endif
        </div>
    </div>
@endif
