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
        'orders.view', 'orders.edit',
        'riders.view', 'riders.create', 'riders.edit', 'riders.delete',
        'dispatch.view', 'dispatch.manage',
        'rider_wallet.view', 'rider_wallet.manage',
        'monitoring.view',
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
            'orders.view', 'orders.edit',
            'riders.view', 'riders.create', 'riders.edit', 'riders.delete',
            'dispatch.view', 'dispatch.manage',
            'rider_wallet.view', 'rider_wallet.manage',
            'monitoring.view',
        ]);

        Role::findByName('Dispatch Manager')->syncPermissions([
            'orders.view', 'orders.edit',
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
            'orders.view',
        ]);

        // Super Admin bypasses permission checks entirely via Gate::before (see AuthServiceProvider).
    }
}
