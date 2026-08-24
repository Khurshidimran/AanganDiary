<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Roles beyond Super Admin/Admin are seeded now so they exist for assignment,
     * but gain permissions as their respective modules (warehouse, dispatch, accounts,
     * purchasing) are built in later phases.
     */
    private const ROLES = [
        'Super Admin',
        'Admin',
        'Warehouse Manager',
        'Dispatch Manager',
        'Accounts User',
        'Purchasing User',
        'Rider',
        'Management Viewer',
    ];

    private const PERMISSIONS = [
        'users.view', 'users.create', 'users.edit', 'users.delete',
        'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        'settings.view', 'settings.edit',
        'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
        'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
        'products.view', 'products.create', 'products.edit', 'products.delete',
        'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
        'units.view', 'units.create', 'units.edit', 'units.delete',
        'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
        'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit', 'purchase_orders.delete', 'purchase_orders.approve',
        'purchase_receipts.view', 'purchase_receipts.create',
        'stock.view',
        'stock_transfers.view', 'stock_transfers.create', 'stock_transfers.edit', 'stock_transfers.delete', 'stock_transfers.approve',
        'stock_adjustments.view', 'stock_adjustments.create', 'stock_adjustments.edit', 'stock_adjustments.delete',
        'shopify.view', 'shopify.sync',
        'orders.view', 'orders.create', 'orders.edit', 'orders.record-payment', 'orders.delete',
        'sales.view',
        'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
        'channels.view', 'channels.create', 'channels.edit', 'channels.delete',
        'riders.view', 'riders.create', 'riders.edit', 'riders.delete',
        'dispatch.view', 'dispatch.manage',
        'rider_wallet.view', 'rider_wallet.manage',
        'monitoring.view',
        'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
        'employee_advances.view', 'employee_advances.create', 'employee_advances.edit',
        'payroll.view', 'payroll.generate', 'payroll.approve', 'payroll.pay',
        'expense_categories.view', 'expense_categories.create', 'expense_categories.edit', 'expense_categories.delete',
        'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
        'accounting.manage',
        'journal_entries.view', 'journal_entries.create', 'journal_entries.void',
        'reports.financial.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName('Admin')->syncPermissions([
            'users.view', 'users.create', 'users.edit',
            'roles.view',
            'settings.view',
            'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
            'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'warehouses.view', 'warehouses.create', 'warehouses.edit', 'warehouses.delete',
            'units.view', 'units.create', 'units.edit', 'units.delete',
            'vendors.view', 'vendors.create', 'vendors.edit', 'vendors.delete',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit', 'purchase_orders.delete', 'purchase_orders.approve',
            'purchase_receipts.view', 'purchase_receipts.create',
            'stock.view',
            'stock_transfers.view', 'stock_transfers.create', 'stock_transfers.edit', 'stock_transfers.delete', 'stock_transfers.approve',
            'stock_adjustments.view', 'stock_adjustments.create', 'stock_adjustments.edit', 'stock_adjustments.delete',
            'shopify.view', 'shopify.sync',
            'orders.view', 'orders.create', 'orders.edit', 'orders.record-payment', 'orders.delete',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'channels.view', 'channels.create', 'channels.edit', 'channels.delete',
            'riders.view', 'riders.create', 'riders.edit', 'riders.delete',
            'dispatch.view', 'dispatch.manage',
            'rider_wallet.view', 'rider_wallet.manage',
            'monitoring.view',
            'employees.view', 'employees.create', 'employees.edit', 'employees.delete',
            'employee_advances.view', 'employee_advances.create', 'employee_advances.edit',
            'payroll.view', 'payroll.generate', 'payroll.approve', 'payroll.pay',
            'expense_categories.view', 'expense_categories.create', 'expense_categories.edit', 'expense_categories.delete',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            'accounting.manage',
            'journal_entries.view', 'journal_entries.create', 'journal_entries.void',
            'reports.financial.view',
        ]);

        Role::findByName('Dispatch Manager')->syncPermissions([
            'orders.view', 'orders.create', 'orders.edit', 'orders.record-payment',
            'customers.view', 'customers.create', 'customers.edit',
            'riders.view', 'riders.create', 'riders.edit',
            'dispatch.view', 'dispatch.manage',
            'rider_wallet.view', 'rider_wallet.manage',
            'monitoring.view',
        ]);

        Role::findByName('Warehouse Manager')->syncPermissions([
            'warehouses.view', 'warehouses.edit',
            'products.view', 'categories.view', 'brands.view', 'units.view',
            'purchase_orders.view', 'purchase_receipts.view', 'purchase_receipts.create',
            'stock.view',
            'stock_transfers.view', 'stock_transfers.create', 'stock_transfers.edit',
            'stock_adjustments.view', 'stock_adjustments.create', 'stock_adjustments.edit', 'stock_adjustments.delete',
        ]);

        Role::findByName('Purchasing User')->syncPermissions([
            'products.view', 'categories.view', 'brands.view', 'units.view',
            'vendors.view', 'vendors.create', 'vendors.edit',
            'purchase_orders.view', 'purchase_orders.create', 'purchase_orders.edit',
            'purchase_receipts.view', 'purchase_receipts.create',
            'stock.view',
        ]);

        Role::findByName('Accounts User')->syncPermissions([
            'vendors.view', 'vendors.edit',
            'purchase_orders.view', 'purchase_receipts.view',
            'stock.view',
            'orders.view', 'orders.record-payment',
            'customers.view',
            'employees.view',
            'employee_advances.view', 'employee_advances.create', 'employee_advances.edit',
            'payroll.view', 'payroll.generate', 'payroll.approve', 'payroll.pay',
            'expense_categories.view',
            'expenses.view', 'expenses.create', 'expenses.edit',
            'journal_entries.view', 'journal_entries.create',
            'reports.financial.view',
        ]);

        // Read-only monitoring for Directors/senior management: orders.view already
        // exposes the full Dashboard (its content is gated by orders.view, not a
        // dedicated dashboard permission — see dashboard.blade.php), and Orders/
        // Dispatch Board/Riders/Sales all already hide every mutating action behind
        // permissions this role deliberately never gets (orders.edit, dispatch.manage,
        // riders.edit, rider_wallet.manage, etc.). rider_wallet.view (view-only —
        // distinct from rider_wallet.manage) unlocks the Dashboard's Courier
        // Performance widget and COD alert without granting deposit/pay/adjust.
        Role::findByName('Management Viewer')->syncPermissions([
            'orders.view', 'dispatch.view', 'riders.view', 'rider_wallet.view', 'sales.view',
        ]);

        // Super Admin bypasses permission checks entirely via Gate::before (see AuthServiceProvider).
    }
}
