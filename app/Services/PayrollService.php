<?php

namespace App\Services;

use App\Exceptions\InvalidPayrollStateException;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\RiderWalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Generates and progresses payroll runs through draft -> approved -> paid.
 * Rider employees have their delivery earnings for the period pulled in from
 * the existing RiderWalletService ledger (see deliveryEarningsFor); when a
 * run is marked paid, a matching TYPE_EARNING_PAID entry is posted back to
 * each rider's wallet so the ledger reconciles and the earnings can't also
 * be settled a second time via the standalone rider wallet screen.
 */
class PayrollService
{
    public function __construct(
        private readonly RiderWalletService $wallet,
        private readonly AccountingPostingService $accounting,
    ) {
    }

    public function generateRun(string|Carbon $periodStart, string|Carbon $periodEnd, ?string $notes = null): PayrollRun
    {
        $periodStart = Carbon::parse($periodStart)->startOfDay();
        $periodEnd = Carbon::parse($periodEnd)->endOfDay();

        return DB::transaction(function () use ($periodStart, $periodEnd, $notes) {
            $run = PayrollRun::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => PayrollRun::STATUS_DRAFT,
                'generated_by' => Auth::id(),
                'notes' => $notes,
            ]);

            $employees = Employee::where('employment_status', Employee::STATUS_ACTIVE)
                ->with('user.riderProfile')
                ->get();

            foreach ($employees as $employee) {
                $basicSalary = (float) $employee->basic_salary;
                $deliveryEarnings = $this->deliveryEarningsFor($employee, $periodStart, $periodEnd);
                $grossPay = $basicSalary + $deliveryEarnings;

                PayrollRunItem::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'basic_salary' => $basicSalary,
                    'delivery_earnings' => $deliveryEarnings,
                    'gross_pay' => $grossPay,
                    'total_deductions' => 0,
                    'net_pay' => $grossPay,
                ]);
            }

            return $run->fresh(['items.employee']);
        });
    }

    /**
     * Sums this period's TYPE_EARNING_CREDITED wallet postings for the
     * employee's rider profile (if any). Those postings are stored as
     * negative amounts (they reduce what the rider owes), so abs() is
     * needed to get a positive earnings figure. See DispatchService::markDelivered().
     */
    public function deliveryEarningsFor(Employee $employee, string|Carbon $periodStart, string|Carbon $periodEnd): float
    {
        $rider = $employee->user?->riderProfile;

        if (! $rider) {
            return 0.0;
        }

        $periodStart = Carbon::parse($periodStart)->startOfDay();
        $periodEnd = Carbon::parse($periodEnd)->endOfDay();

        $sum = $rider->walletTransactions()
            ->where('transaction_type', RiderWalletTransaction::TYPE_EARNING_CREDITED)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->sum(DB::raw('ABS(amount)'));

        return (float) $sum;
    }

    public function addAdjustment(
        PayrollRunItem $item,
        string $type,
        string $label,
        float $amount,
        ?EmployeeAdvance $advance = null,
    ): PayrollAdjustment {
        return DB::transaction(function () use ($item, $type, $label, $amount, $advance) {
            $run = $item->payrollRun()->lockForUpdate()->first();

            if (! $run->isEditable()) {
                throw new InvalidPayrollStateException('Adjustments can only be made while the payroll run is a draft.');
            }

            $adjustment = PayrollAdjustment::create([
                'payroll_run_item_id' => $item->id,
                'employee_advance_id' => $advance?->id,
                'type' => $type,
                'label' => $label,
                'amount' => $amount,
                'recorded_by' => Auth::id(),
            ]);

            if ($advance && $type === PayrollAdjustment::TYPE_DEDUCTION) {
                $remaining = max(0, (float) $advance->remaining_balance - $amount);
                $advance->update([
                    'remaining_balance' => $remaining,
                    'status' => $remaining <= 0 ? EmployeeAdvance::STATUS_SETTLED : EmployeeAdvance::STATUS_ACTIVE,
                ]);
            }

            $this->recalculateItem($item);

            return $adjustment;
        });
    }

    public function removeAdjustment(PayrollAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            $item = $adjustment->payrollRunItem;
            $run = $item->payrollRun()->lockForUpdate()->first();

            if (! $run->isEditable()) {
                throw new InvalidPayrollStateException('Adjustments can only be removed while the payroll run is a draft.');
            }

            $advance = $adjustment->employeeAdvance;

            if ($advance && $adjustment->type === PayrollAdjustment::TYPE_DEDUCTION) {
                $advance->update([
                    'remaining_balance' => (float) $advance->remaining_balance + (float) $adjustment->amount,
                    'status' => EmployeeAdvance::STATUS_ACTIVE,
                ]);
            }

            $adjustment->delete();

            $this->recalculateItem($item);
        });
    }

    private function recalculateItem(PayrollRunItem $item): void
    {
        $item->refresh();

        $additions = (float) $item->adjustments()->where('type', PayrollAdjustment::TYPE_ADDITION)->sum('amount');
        $deductions = (float) $item->adjustments()->where('type', PayrollAdjustment::TYPE_DEDUCTION)->sum('amount');

        $item->update([
            'total_deductions' => $deductions,
            'net_pay' => (float) $item->gross_pay + $additions - $deductions,
        ]);
    }

    public function approve(PayrollRun $run): PayrollRun
    {
        if (! $run->canBeApproved()) {
            throw new InvalidPayrollStateException('Only draft payroll runs can be approved.');
        }

        $run->update([
            'status' => PayrollRun::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return $run;
    }

    public function markPaid(PayrollRun $run): PayrollRun
    {
        if (! $run->canBeMarkedPaid()) {
            throw new InvalidPayrollStateException('Only approved payroll runs can be marked paid.');
        }

        return DB::transaction(function () use ($run) {
            $items = $run->items()->with('employee.user.riderProfile')->get();

            foreach ($items as $item) {
                if ((float) $item->delivery_earnings <= 0) {
                    continue;
                }

                $rider = $item->employee->user?->riderProfile;

                if (! $rider) {
                    continue;
                }

                $this->wallet->postTransaction(
                    rider: $rider,
                    transactionType: RiderWalletTransaction::TYPE_EARNING_PAID,
                    amount: (float) $item->delivery_earnings,
                    referenceType: 'payroll_run_items',
                    referenceId: $item->id,
                    notes: "Delivery earnings paid via payroll run {$run->period_start->toDateString()} to {$run->period_end->toDateString()}",
                );
            }

            $run->update([
                'status' => PayrollRun::STATUS_PAID,
                'paid_at' => now(),
            ]);

            $this->accounting->postPayrollEntry($run);

            return $run->fresh();
        });
    }
}
