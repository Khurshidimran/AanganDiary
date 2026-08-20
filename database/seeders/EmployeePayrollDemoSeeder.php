<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\PayrollRun;
use App\Models\RiderProfile;
use App\Models\RiderWalletTransaction;
use App\Models\User;
use App\Services\PayrollService;
use App\Services\RiderWalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Demo data for the HRM/Payroll module: office staff (linked to the existing
 * role-demo users), the existing rider as an employee, a couple of brand-new
 * production staff, some advances, and two payroll runs (one fully paid
 * covering last period, one still in draft covering the current period) so
 * every module screen has something to show. Safe to re-run — every write is
 * guarded so it won't duplicate employees/advances/runs on a second pass.
 */
class EmployeePayrollDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('Super Admin')->first() ?? User::first();
        Auth::login($admin);

        $employees = $this->seedEmployees();
        $this->seedRiderEarnings($employees['rider']->user->riderProfile);
        $advances = $this->seedAdvances($employees, $admin);
        $this->seedPayrollRuns($employees, $advances);

        Auth::logout();

        $this->command->info('Employee/Payroll demo data seeded.');
    }

    /**
     * @return array<string, Employee>
     */
    private function seedEmployees(): array
    {
        $linked = [
            'admin' => ['email' => 'admin@aangandairy.com', 'code' => 'EMP-0001', 'designation' => 'Operations Manager', 'department' => 'Management', 'joining_date' => '2024-01-15', 'basic_salary' => 80000, 'cnic' => '35202-1000001-1'],
            'warehouse' => ['email' => 'warehouse.manager@aangandairy.com', 'code' => 'EMP-0002', 'designation' => 'Warehouse Manager', 'department' => 'Warehouse', 'joining_date' => '2024-02-01', 'basic_salary' => 65000, 'cnic' => '35202-1000002-1'],
            'dispatch' => ['email' => 'dispatch.manager@aangandairy.com', 'code' => 'EMP-0003', 'designation' => 'Dispatch Manager', 'department' => 'Dispatch', 'joining_date' => '2024-02-01', 'basic_salary' => 60000, 'cnic' => '35202-1000003-1'],
            'accounts' => ['email' => 'accounts@aangandairy.com', 'code' => 'EMP-0004', 'designation' => 'Accounts Officer', 'department' => 'Finance', 'joining_date' => '2024-03-01', 'basic_salary' => 50000, 'cnic' => '35202-1000004-1'],
            'purchasing' => ['email' => 'purchasing@aangandairy.com', 'code' => 'EMP-0005', 'designation' => 'Purchasing Officer', 'department' => 'Procurement', 'joining_date' => '2024-03-01', 'basic_salary' => 50000, 'cnic' => '35202-1000005-1'],
        ];

        $result = [];

        foreach ($linked as $key => $data) {
            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                $this->command->warn("Skipping employee link for {$data['email']} — user not found (run DummyDataSeeder first).");

                continue;
            }

            $result[$key] = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $data['code'],
                    'designation' => $data['designation'],
                    'department' => $data['department'],
                    'employment_type' => Employee::EMPLOYMENT_TYPE_FULL_TIME,
                    'joining_date' => $data['joining_date'],
                    'cnic' => $data['cnic'],
                    'bank_name' => 'Meezan Bank',
                    'bank_account_number' => '01-' . random_int(1000000, 9999999),
                    'basic_salary' => $data['basic_salary'],
                    'employment_status' => Employee::STATUS_ACTIVE,
                ],
            );
        }

        $riderUser = User::whereHas('riderProfile')->first();

        if ($riderUser) {
            $result['rider'] = Employee::firstOrCreate(
                ['user_id' => $riderUser->id],
                [
                    'employee_code' => 'EMP-0006',
                    'designation' => 'Delivery Rider',
                    'department' => 'Dispatch',
                    'employment_type' => Employee::EMPLOYMENT_TYPE_FULL_TIME,
                    'joining_date' => '2024-04-01',
                    'cnic' => '35202-1000006-1',
                    'bank_name' => 'JazzCash',
                    'bank_account_number' => $riderUser->phone ?? '0300-0000000',
                    'basic_salary' => 18000,
                    'employment_status' => Employee::STATUS_ACTIVE,
                ],
            );
        }

        $newStaff = [
            'packer' => ['name' => 'Ayesha Bibi', 'email' => 'ayesha.bibi@aangandairy.com', 'code' => 'EMP-0007', 'designation' => 'Packing Staff', 'department' => 'Production', 'joining_date' => '2024-05-10', 'basic_salary' => 28000],
            'operator' => ['name' => 'Usman Tariq', 'email' => 'usman.tariq@aangandairy.com', 'code' => 'EMP-0008', 'designation' => 'Machine Operator', 'department' => 'Production', 'joining_date' => '2024-05-10', 'basic_salary' => 32000],
            'qc' => ['name' => 'Fatima Noreen', 'email' => 'fatima.noreen@aangandairy.com', 'code' => 'EMP-0009', 'designation' => 'Quality Control Assistant', 'department' => 'Quality Control', 'joining_date' => '2024-06-01', 'basic_salary' => 30000],
        ];

        foreach ($newStaff as $key => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ],
            );

            $result[$key] = Employee::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $data['code'],
                    'designation' => $data['designation'],
                    'department' => $data['department'],
                    'employment_type' => Employee::EMPLOYMENT_TYPE_FULL_TIME,
                    'joining_date' => $data['joining_date'],
                    'basic_salary' => $data['basic_salary'],
                    'employment_status' => Employee::STATUS_ACTIVE,
                ],
            );
        }

        return $result;
    }

    /**
     * Backdated delivery-earnings postings for last period and this period,
     * so PayrollService::deliveryEarningsFor() has real ledger entries to
     * pull in when the demo payroll runs are generated below.
     */
    private function seedRiderEarnings(?RiderProfile $rider): void
    {
        if (! $rider || $rider->walletTransactions()->exists()) {
            return;
        }

        $wallet = app(RiderWalletService::class);
        $demoRate = 60.0;

        $lastMonthMid = now()->subMonthNoOverflow()->startOfMonth()->addDays(10);
        foreach (range(1, 4) as $i) {
            $wallet->postTransaction(
                $rider, RiderWalletTransaction::TYPE_EARNING_CREDITED, -$demoRate,
                'orders', (string) Str::uuid(), "Demo delivery fee #{$i} (last period)",
            );
        }
        RiderWalletTransaction::where('rider_id', $rider->id)->update(['created_at' => $lastMonthMid, 'updated_at' => $lastMonthMid]);

        $thisMonthEarly = now()->startOfMonth()->addDays(5);
        $before = RiderWalletTransaction::where('rider_id', $rider->id)->pluck('id');
        foreach (range(1, 5) as $i) {
            $wallet->postTransaction(
                $rider, RiderWalletTransaction::TYPE_EARNING_CREDITED, -$demoRate,
                'orders', (string) Str::uuid(), "Demo delivery fee #{$i} (this period)",
            );
        }
        RiderWalletTransaction::where('rider_id', $rider->id)->whereNotIn('id', $before)
            ->update(['created_at' => $thisMonthEarly, 'updated_at' => $thisMonthEarly]);
    }

    /**
     * @param array<string, Employee> $employees
     * @return array<string, EmployeeAdvance>
     */
    private function seedAdvances(array $employees, User $admin): array
    {
        $advances = [];

        if (isset($employees['packer'])) {
            $advances['packer'] = EmployeeAdvance::firstOrCreate(
                ['employee_id' => $employees['packer']->id, 'reason' => 'Medical emergency'],
                [
                    'amount' => 5000, 'date_given' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
                    'remaining_balance' => 5000, 'status' => EmployeeAdvance::STATUS_ACTIVE, 'recorded_by' => $admin->id,
                ],
            );
        }

        if (isset($employees['operator'])) {
            $advances['operator'] = EmployeeAdvance::firstOrCreate(
                ['employee_id' => $employees['operator']->id, 'reason' => 'Eid advance'],
                [
                    'amount' => 4000, 'date_given' => now()->subMonthNoOverflow()->startOfMonth()->addDays(1),
                    'remaining_balance' => 4000, 'status' => EmployeeAdvance::STATUS_ACTIVE, 'recorded_by' => $admin->id,
                ],
            );
        }

        return $advances;
    }

    /**
     * @param array<string, Employee> $employees
     * @param array<string, EmployeeAdvance> $advances
     */
    private function seedPayrollRuns(array $employees, array $advances): void
    {
        $payroll = app(PayrollService::class);

        $lastPeriodStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastPeriodEnd = now()->subMonthNoOverflow()->endOfMonth();

        if (! PayrollRun::whereDate('period_start', $lastPeriodStart)->exists()) {
            $run = $payroll->generateRun($lastPeriodStart, $lastPeriodEnd, 'Demo run — previous period (paid).');

            if (isset($advances['packer'])) {
                $item = $run->items->firstWhere('employee_id', $employees['packer']->id);
                $payroll->addAdjustment($item, 'deduction', 'Advance repayment', 2000, $advances['packer']->fresh());
            }

            if (isset($advances['operator'])) {
                $item = $run->items->firstWhere('employee_id', $employees['operator']->id);
                $payroll->addAdjustment($item, 'deduction', 'Advance repayment (final)', 4000, $advances['operator']->fresh());
            }

            if (isset($employees['admin'])) {
                $item = $run->items->firstWhere('employee_id', $employees['admin']->id);
                $payroll->addAdjustment($item, 'addition', 'Performance bonus', 5000);
            }

            $payroll->approve($run);
            $payroll->markPaid($run);
        }

        $thisPeriodStart = now()->startOfMonth();
        $thisPeriodEnd = now()->endOfMonth();

        if (! PayrollRun::whereDate('period_start', $thisPeriodStart)->exists()) {
            $run = $payroll->generateRun($thisPeriodStart, $thisPeriodEnd, 'Demo run — current period (draft).');

            if (isset($advances['packer'])) {
                $item = $run->items->firstWhere('employee_id', $employees['packer']->id);
                $payroll->addAdjustment($item, 'deduction', 'Advance repayment', 1000, $advances['packer']->fresh());
            }
        }
    }
}
