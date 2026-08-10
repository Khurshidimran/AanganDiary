<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::orderBy('name')->pluck('name');

        return view('users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
                'status' => $validated['status'],
            ]);

            $user->syncRoles($validated['roles']);

            return $user;
        });

        $this->auditLog->log('created', 'users', $user, null, $user->only(['name', 'email', 'status']));

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $roles = Role::orderBy('name')->pluck('name');

        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $before = $user->only(['name', 'email', 'status']);

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'status' => $validated['status'],
            ]);

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
            }

            $user->save();
            $user->syncRoles($validated['roles']);
        });

        $this->auditLog->log('updated', 'users', $user, $before, $user->only(['name', 'email', 'status']));

        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $before = $user->only(['name', 'email', 'status']);
        $user->delete();

        $this->auditLog->log('deleted', 'users', null, $before, null);

        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }
}
