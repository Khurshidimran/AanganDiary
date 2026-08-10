@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <h1 class="h4 mb-3">Core Settings</h1>

    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="company_name" class="form-label">Company Name</label>
                    <input id="company_name" type="text" name="company_name"
                           value="{{ old('company_name', $settings->get('company_name')) }}"
                           class="form-control @error('company_name') is-invalid @enderror" required>
                    @error('company_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="company_email" class="form-label">Company Email</label>
                    <input id="company_email" type="email" name="company_email"
                           value="{{ old('company_email', $settings->get('company_email')) }}"
                           class="form-control @error('company_email') is-invalid @enderror">
                    @error('company_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="company_phone" class="form-label">Company Phone</label>
                    <input id="company_phone" type="text" name="company_phone"
                           value="{{ old('company_phone', $settings->get('company_phone')) }}"
                           class="form-control @error('company_phone') is-invalid @enderror">
                    @error('company_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="company_address" class="form-label">Company Address</label>
                    <textarea id="company_address" name="company_address" rows="3"
                              class="form-control @error('company_address') is-invalid @enderror">{{ old('company_address', $settings->get('company_address')) }}</textarea>
                    @error('company_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">Inventory Settings</h2>
    <div class="card shadow-sm" style="max-width: 560px;">
        <div class="card-body">
            <form method="POST" action="{{ route('settings.inventory.update') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="default_warehouse_id" class="form-label">Default Warehouse (for online orders)</label>
                    <select id="default_warehouse_id" name="default_warehouse_id"
                            class="form-select @error('default_warehouse_id') is-invalid @enderror" required>
                        <option value="">Select a warehouse</option>
                        @foreach ($warehouses as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('default_warehouse_id', $inventorySettings->get('default_warehouse_id')) === (string) $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('default_warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Stock is deducted from this warehouse when an online order is confirmed.</div>
                </div>

                <button type="submit" class="btn btn-primary">Save Inventory Settings</button>
            </form>
        </div>
    </div>
@endsection
