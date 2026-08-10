@php
    $userRoles = $user?->roles->pluck('name')->all() ?? [];
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $user?->name) }}"
           class="form-control @error('name') is-invalid @enderror" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}"
           class="form-control @error('email') is-invalid @enderror" required>
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone', $user?->phone) }}"
           class="form-control @error('phone') is-invalid @enderror">
    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="password" class="form-label">Password @if($user) <span class="text-muted small">(leave blank to keep current)</span> @endif</label>
    <input id="password" type="password" name="password"
           class="form-control @error('password') is-invalid @enderror" {{ $user ? '' : 'required' }}>
    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="password_confirmation" class="form-label">Confirm Password</label>
    <input id="password_confirmation" type="password" name="password_confirmation" class="form-control">
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
        @foreach (['active', 'inactive'] as $status)
            <option value="{{ $status }}" @selected(old('status', $user?->status ?? 'active') === $status)>
                {{ ucfirst($status) }}
            </option>
        @endforeach
    </select>
    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Roles</label>
    @foreach ($roles as $role)
        <div class="form-check">
            <input type="checkbox" name="roles[]" value="{{ $role }}" id="role-{{ $role }}"
                   class="form-check-input" @checked(in_array($role, old('roles', $userRoles)))>
            <label for="role-{{ $role }}" class="form-check-label">{{ $role }}</label>
        </div>
    @endforeach
    @error('roles') <div class="text-danger small">{{ $message }}</div> @enderror
</div>
