@php
    $rolePermissions = $role?->permissions->pluck('name')->all() ?? [];
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Role Name</label>
    <input id="name" type="text" name="name" value="{{ old('name', $role?->name) }}"
           class="form-control @error('name') is-invalid @enderror"
           {{ $role?->name === 'Super Admin' ? 'readonly' : '' }} required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label">Permissions</label>
    @foreach ($permissions as $permission)
        <div class="form-check">
            <input type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm-{{ $permission }}"
                   class="form-check-input" @checked(in_array($permission, old('permissions', $rolePermissions)))>
            <label for="perm-{{ $permission }}" class="form-check-label">{{ $permission }}</label>
        </div>
    @endforeach
    @error('permissions') <div class="text-danger small">{{ $message }}</div> @enderror
</div>
