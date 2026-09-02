<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * [code, name, type, parent_code, is_system]
     */
    private const ACCOUNTS = [
        ['1000', 'Assets', 'asset', null, true],
        ['1010', 'Cash in Hand', 'asset', '1000', true],
        ['1020', 'Bank Account', 'asset', '1000', true],
        ['1100', 'Accounts Receivable', 'asset', '1000', true],
        ['1200', 'Inventory Asset', 'asset', '1000', true],
        ['1300', 'Purchases', 'asset', '1000', false],
        ['1310', 'Dairy', 'asset', '1300', false],
        ['1320', 'Packing', 'asset', '1300', false],
        ['2000', 'Liabilities', 'liability', null, true],
        ['2100', 'Accounts Payable', 'liability', '2000', true],
        ['2500', 'Payable', 'liability', '2000', false],
        ['2200', 'Salaries Payable', 'liability', '2500', true],
        ['2520', 'Rent Payable', 'liability', '2500', false],
        ['2300', 'Tax Payable', 'liability', '2000', true],
        ['2400', 'Payroll Deductions Clearing', 'liability', '2000', true],
        ['3000', 'Equity', 'equity', null, true],
        ['3100', "Owner's Equity", 'equity', '3000', false],
        ['3200', 'Retained Earnings', 'equity', '3000', true],
        ['4000', 'Revenue', 'revenue', null, true],
        ['4100', 'Sales Revenue', 'revenue', '4000', true],
        ['5000', 'Expenses', 'expense', null, true],
        ['5100', 'Cost of Goods Sold', 'expense', '5000', true],
        ['5200', 'Salaries & Wages Expense', 'expense', '5000', true],
        ['5990', 'Miscellaneous Expense', 'expense', '5000', false],
        ['6000', 'Direct Expenses', 'expense', '5000', false],
        ['6010', 'Fuel & Transport', 'expense', '6000', false],
        ['6020', 'Bill', 'expense', '6000', false],
        ['6030', 'Mobile', 'expense', '6000', false],
        ['6040', 'Water', 'expense', '6000', false],
        ['6100', 'Indirect Expenses', 'expense', '5000', false],
        ['6110', 'Packaging Material', 'expense', '6100', false],
    ];

    private const DEFAULT_MAPPING = [
        'sales_revenue_account_id' => '4100',
        'receivable_account_id' => '1100',
        'cash_account_id' => '1010',
        'bank_account_id' => '1020',
        'inventory_asset_account_id' => '1200',
        'cogs_account_id' => '5100',
        'payable_account_id' => '2100',
        'salaries_expense_account_id' => '5200',
        'salaries_payable_account_id' => '2200',
        'payroll_cash_account_id' => '1020',
        'payroll_deductions_account_id' => '2400',
        'default_expense_account_id' => '5990',
        'tax_payable_account_id' => '2300',
    ];

    public function run(): void
    {
        $byCode = [];

        foreach (self::ACCOUNTS as [$code, $name, $type, $parentCode, $isSystem]) {
            $byCode[$code] = Account::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'parent_id' => $parentCode ? $byCode[$parentCode]->id : null,
                    'is_system' => $isSystem,
                    'status' => Account::STATUS_ACTIVE,
                ],
            );
        }

        $categoryAccountCodes = [
            'Fuel & Transport' => '6010',
            'Bill' => '6020',
            'Mobile' => '6030',
            'Water' => '6040',
            'Packaging Material' => '6110',
            'Rent' => '2520',
            'Salaries' => '2200',
        ];

        foreach ($categoryAccountCodes as $categoryName => $code) {
            ExpenseCategory::where('name', $categoryName)->update(['chart_account_id' => $byCode[$code]->id]);
        }

        // One expense account per remaining expense category, linked back so
        // expense postings have a sensible default from day one.
        $expenseParent = $byCode['5000'];
        $nextCode = 5300;

        foreach (ExpenseCategory::whereNull('chart_account_id')->get() as $category) {
            while (Account::where('code', (string) $nextCode)->exists()) {
                $nextCode += 100;
            }

            $account = Account::create([
                'code' => (string) $nextCode,
                'name' => $category->name,
                'type' => Account::TYPE_EXPENSE,
                'parent_id' => $expenseParent->id,
                'is_system' => false,
                'status' => Account::STATUS_ACTIVE,
            ]);

            $category->update(['chart_account_id' => $account->id]);
            $nextCode += 100;
        }

        $settings = app(SettingsService::class);

        foreach (self::DEFAULT_MAPPING as $key => $code) {
            $settings->set($key, $byCode[$code]->id, 'accounting');
        }
    }
}
