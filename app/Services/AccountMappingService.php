<?php

namespace App\Services;

use App\Models\Account;

/**
 * Typed accessors over Settings(group: 'accounting') — reuses the existing
 * key-value Setting/SettingsService infrastructure (see SettingsController)
 * rather than introducing a new model just for a handful of account picks.
 */
class AccountMappingService
{
    public const KEYS = [
        'sales_revenue_account_id',
        'receivable_account_id',
        'cash_account_id',
        'bank_account_id',
        'inventory_asset_account_id',
        'cogs_account_id',
        'payable_account_id',
        'salaries_expense_account_id',
        'salaries_payable_account_id',
        'payroll_cash_account_id',
        'payroll_deductions_account_id',
        'default_expense_account_id',
        'tax_payable_account_id',
    ];

    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function salesRevenueAccount(): ?Account
    {
        return $this->accountFor('sales_revenue_account_id');
    }

    public function receivableAccount(): ?Account
    {
        return $this->accountFor('receivable_account_id');
    }

    public function cashAccount(): ?Account
    {
        return $this->accountFor('cash_account_id');
    }

    public function bankAccount(): ?Account
    {
        return $this->accountFor('bank_account_id');
    }

    public function inventoryAssetAccount(): ?Account
    {
        return $this->accountFor('inventory_asset_account_id');
    }

    public function cogsAccount(): ?Account
    {
        return $this->accountFor('cogs_account_id');
    }

    public function payableAccount(): ?Account
    {
        return $this->accountFor('payable_account_id');
    }

    public function salariesExpenseAccount(): ?Account
    {
        return $this->accountFor('salaries_expense_account_id');
    }

    public function salariesPayableAccount(): ?Account
    {
        return $this->accountFor('salaries_payable_account_id');
    }

    public function payrollCashAccount(): ?Account
    {
        return $this->accountFor('payroll_cash_account_id');
    }

    public function payrollDeductionsAccount(): ?Account
    {
        return $this->accountFor('payroll_deductions_account_id');
    }

    public function defaultExpenseAccount(): ?Account
    {
        return $this->accountFor('default_expense_account_id');
    }

    public function taxPayableAccount(): ?Account
    {
        return $this->accountFor('tax_payable_account_id');
    }

    private function accountFor(string $key): ?Account
    {
        $id = $this->settings->group('accounting')->get($key);

        return $id ? Account::find($id) : null;
    }
}
