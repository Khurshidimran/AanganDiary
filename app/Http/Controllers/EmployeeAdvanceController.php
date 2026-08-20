<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmployeeAdvanceController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('employee_advances.view');

        $advances = EmployeeAdvance::with(['employee.user', 'recordedBy'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('date_given')
            ->paginate(20)
            ->withQueryString();

        $employees = Employee::with('user')->get()->sortBy(fn ($e) => $e->user->name)->pluck('user.name', 'id');

        $totals = [
            'outstanding' => EmployeeAdvance::where('status', EmployeeAdvance::STATUS_ACTIVE)->sum('remaining_balance'),
        ];

        return view('advances.index', compact('advances', 'employees', 'totals'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('employee_advances.create');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date_given' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $advance = EmployeeAdvance::create([
            'employee_id' => $validated['employee_id'],
            'amount' => $validated['amount'],
            'date_given' => $validated['date_given'],
            'reason' => $validated['reason'] ?? null,
            'remaining_balance' => $validated['amount'],
            'status' => EmployeeAdvance::STATUS_ACTIVE,
            'recorded_by' => Auth::id(),
        ]);

        $this->auditLog->log('created', 'employee_advances', $advance, null, $advance->only(['amount', 'date_given']));

        return back()->with('status', 'Advance recorded.');
    }

    /**
     * Closes out an advance without a payroll deduction — for advances that
     * will never be repaid through payroll (e.g. the employee left, or it
     * was forgiven). Payroll-driven repayment already settles advances via
     * PayrollService::addAdjustment(); this is the manual equivalent.
     */
    public function writeOff(EmployeeAdvance $advance): RedirectResponse
    {
        $this->authorize('employee_advances.edit');

        if ($advance->status === EmployeeAdvance::STATUS_SETTLED) {
            return back()->with('error', 'This advance is already settled.');
        }

        $before = $advance->only(['remaining_balance', 'status']);

        $advance->update(['remaining_balance' => 0, 'status' => EmployeeAdvance::STATUS_SETTLED]);

        $this->auditLog->log('written_off', 'employee_advances', $advance, $before, $advance->only(['remaining_balance', 'status']));

        return back()->with('status', 'Advance written off.');
    }
}
