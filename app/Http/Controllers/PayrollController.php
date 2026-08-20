<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidPayrollStateException;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\AuditLogService;
use App\Services\JournalEntryService;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollService $payroll,
        private readonly AuditLogService $auditLog,
        private readonly JournalEntryService $journal,
    ) {
    }

    public function index(): View
    {
        $this->authorize('payroll.view');

        $runs = PayrollRun::withCount('items')->orderBy('period_start', 'desc')->paginate(20);

        return view('payroll.index', compact('runs'));
    }

    public function create(): View
    {
        $this->authorize('payroll.generate');

        return view('payroll.create');
    }

    public function adjustmentsIndex(Request $request): View
    {
        $this->authorize('payroll.view');

        $adjustments = PayrollAdjustment::with(['payrollRunItem.employee.user', 'payrollRunItem.payrollRun', 'employeeAdvance', 'recordedBy'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('employee_id'), fn ($q) => $q->whereHas(
                'payrollRunItem',
                fn ($qq) => $qq->where('employee_id', $request->employee_id),
            ))
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        $employees = Employee::with('user')->get()->sortBy(fn ($e) => $e->user->name)->pluck('user.name', 'id');

        return view('payroll.adjustments', compact('adjustments', 'employees'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('payroll.generate');

        $validated = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string'],
        ]);

        $run = $this->payroll->generateRun($validated['period_start'], $validated['period_end'], $validated['notes'] ?? null);

        $this->auditLog->log('generated', 'payroll_runs', $run, null, [
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
        ]);

        return redirect()->route('payroll.show', $run)->with('status', 'Payroll run generated.');
    }

    public function show(PayrollRun $payrollRun): View
    {
        $this->authorize('payroll.view');

        $payrollRun->load([
            'items.employee.user',
            'items.adjustments.employeeAdvance',
            'generatedBy',
            'approvedBy',
        ]);

        $advancesByEmployee = EmployeeAdvance::where('status', EmployeeAdvance::STATUS_ACTIVE)
            ->get()
            ->groupBy('employee_id');

        return view('payroll.show', compact('payrollRun', 'advancesByEmployee'));
    }

    public function approve(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('payroll.approve');

        try {
            $this->payroll->approve($payrollRun);
        } catch (InvalidPayrollStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('approved', 'payroll_runs', $payrollRun, null, ['status' => $payrollRun->status]);

        return back()->with('status', 'Payroll run approved.');
    }

    public function pay(PayrollRun $payrollRun): RedirectResponse
    {
        $this->authorize('payroll.pay');

        try {
            $this->payroll->markPaid($payrollRun);
        } catch (InvalidPayrollStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('paid', 'payroll_runs', $payrollRun, null, ['status' => $payrollRun->status]);

        $accountingNote = $this->journal->hasPostedEntryFor('payroll_runs', $payrollRun->id)
            ? ''
            : ' Accounting entry was not posted — finish Account Mapping setup.';

        return back()->with('status', 'Payroll run marked as paid. Rider wallets reconciled for delivery earnings.'.$accountingNote);
    }

    public function storeAdjustment(Request $request, PayrollRunItem $payrollRunItem): RedirectResponse
    {
        $this->authorize('payroll.generate');

        $validated = $request->validate([
            'type' => ['required', 'in:addition,deduction'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'employee_advance_id' => ['nullable', 'exists:employee_advances,id'],
        ]);

        $advance = null;

        if (! empty($validated['employee_advance_id'])) {
            $advance = EmployeeAdvance::where('id', $validated['employee_advance_id'])
                ->where('employee_id', $payrollRunItem->employee_id)
                ->firstOrFail();
        }

        try {
            $this->payroll->addAdjustment(
                $payrollRunItem,
                $validated['type'],
                $validated['label'],
                (float) $validated['amount'],
                $advance,
            );
        } catch (InvalidPayrollStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('adjustment_added', 'payroll_runs', $payrollRunItem->payrollRun, null, $validated);

        return back()->with('status', 'Adjustment added.');
    }

    public function destroyAdjustment(PayrollAdjustment $adjustment): RedirectResponse
    {
        $this->authorize('payroll.generate');

        $run = $adjustment->payrollRunItem->payrollRun;

        try {
            $this->payroll->removeAdjustment($adjustment);
        } catch (InvalidPayrollStateException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditLog->log('adjustment_removed', 'payroll_runs', $run, null, null);

        return back()->with('status', 'Adjustment removed.');
    }
}
