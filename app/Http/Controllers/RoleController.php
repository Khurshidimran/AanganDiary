<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')->orderBy('name')->paginate(20);

        return view('roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        $permissions = Permission::orderBy('name')->pluck('name');

        return view('roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = DB::transaction(function () use ($validated) {
            $role = Role::create(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);

            return $role;
        });

        $this->auditLog->log('created', 'roles', null, null, ['name' => $role->name]);

        return redirect()->route('roles.index')->with('status', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        $permissions = Permission::orderBy('name')->pluck('name');

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();
        $before = ['name' => $role->name];

        DB::transaction(function () use ($role, $validated) {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);
        });

        $this->auditLog->log('updated', 'roles', null, $before, ['name' => $role->name]);

        return redirect()->route('roles.index')->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $before = ['name' => $role->name];
        $role->delete();

        $this->auditLog->log('deleted', 'roles', null, $before, null);

        return redirect()->route('roles.index')->with('status', 'Role deleted successfully.');
    }
}
