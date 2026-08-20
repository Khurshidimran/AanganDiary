<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::with('user.riderProfile')->orderBy('created_at', 'desc')->paginate(20);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('employees.create', [
            'availableUsers' => User::doesntHave('employee')->orderBy('name')->pluck('name', 'id'),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $employee = DB::transaction(function () use ($validated) {
            if ($validated['user_mode'] === 'new') {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => $validated['password'],
                    'status' => 'active',
                ]);

                if (! empty($validated['roles'])) {
                    $user->syncRoles($validated['roles']);
                }

                $userId = $user->id;
            } else {
                $userId = $validated['user_id'];
            }

            return Employee::create([
                'user_id' => $userId,
                'employee_code' => $validated['employee_code'],
                'designation' => $validated['designation'] ?? null,
                'department' => $validated['department'] ?? null,
                'employment_type' => $validated['employment_type'],
                'joining_date' => $validated['joining_date'] ?? null,
                'cnic' => $validated['cnic'] ?? null,
                'date_of_birth' => $validated['date_of_birth'] ?? null,
                'address' => $validated['address'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'basic_salary' => $validated['basic_salary'],
                'employment_status' => $validated['employment_status'],
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $this->auditLog->log('created', 'employees', $employee, null, $employee->only(['employee_code', 'employment_status']));

        return redirect()->route('employees.index')->with('status', 'Employee created successfully.');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);

        $employee->load('user.riderProfile', 'advances.recordedBy');

        $payrollHistory = $employee->payrollRunItems()->with('payrollRun')->latest('created_at')->take(12)->get();

        return view('employees.show', compact('employee', 'payrollHistory'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);

        $employee->load('user');

        return view('employees.edit', compact('employee'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();
        $before = $employee->only(['employee_code', 'employment_status', 'basic_salary']);

        $employee->update($validated);

        $this->auditLog->log('updated', 'employees', $employee, $before, $employee->only(['employee_code', 'employment_status', 'basic_salary']));

        return redirect()->route('employees.index')->with('status', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->authorize('delete', $employee);

        if ($employee->advances()->where('status', EmployeeAdvance::STATUS_ACTIVE)->exists()) {
            return back()->with('error', 'Cannot delete an employee with an unsettled advance. Settle it first.');
        }

        $before = $employee->only(['employee_code', 'employment_status']);
        $employee->delete();

        $this->auditLog->log('deleted', 'employees', null, $before, null);

        return redirect()->route('employees.index')->with('status', 'Employee deleted successfully.');
    }
}
