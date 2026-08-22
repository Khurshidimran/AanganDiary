<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountingReportController;
use App\Http\Controllers\AccountMappingController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\EmployeeAdvanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\RiderAccountController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\RiderWalletController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyOAuthController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('channels', ChannelController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('brands', BrandController::class)->except(['show']);
    Route::resource('units', UnitController::class)->except(['show']);
    Route::resource('warehouses', WarehouseController::class)->except(['show']);
    Route::get('products/report', [ProductController::class, 'report'])->name('products.report');
    Route::resource('products', ProductController::class)->except(['show']);

    Route::resource('vendors', VendorController::class);
    Route::post('vendors/{vendor}/payments', [VendorPaymentController::class, 'store'])->name('vendors.payments.store');

    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');

    Route::resource('purchase-receipts', PurchaseReceiptController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('stock-balances', [StockBalanceController::class, 'index'])->name('stock-balances.index');

    Route::resource('stock-transfers', StockTransferController::class);
    Route::post('stock-transfers/{stock_transfer}/request', [StockTransferController::class, 'request'])->name('stock-transfers.request');
    Route::post('stock-transfers/{stock_transfer}/approve', [StockTransferController::class, 'approve'])->name('stock-transfers.approve');
    Route::post('stock-transfers/{stock_transfer}/dispatch', [StockTransferController::class, 'dispatch'])->name('stock-transfers.dispatch');
    Route::post('stock-transfers/{stock_transfer}/receive', [StockTransferController::class, 'receive'])->name('stock-transfers.receive');
    Route::post('stock-transfers/{stock_transfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');

    Route::resource('stock-adjustments', StockAdjustmentController::class);
    Route::post('stock-adjustments/{stock_adjustment}/post', [StockAdjustmentController::class, 'post'])->name('stock-adjustments.post');

    // Registered before the orders resource so this literal path takes
    // priority over the resource's GET orders/{order} wildcard match.
    Route::get('orders/print-labels', [OrderController::class, 'bulkLabels'])->name('orders.labels.bulk');

    Route::get('customers/search', [CustomerController::class, 'search'])->name('customers.search');
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::post('customers/{customer}/addresses', [CustomerAddressController::class, 'store'])->name('customers.addresses.store');
    Route::put('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'update'])->name('customers.addresses.update');
    Route::delete('customers/{customer}/addresses/{address}', [CustomerAddressController::class, 'destroy'])->name('customers.addresses.destroy');

    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('orders/{order}/label', [OrderController::class, 'label'])->name('orders.label');
    Route::get('orders/export/pdf', [OrderController::class, 'exportPdf'])->name('orders.export.pdf');
    Route::get('orders/export/excel', [OrderController::class, 'exportExcel'])->name('orders.export.excel');
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('orders/{order}/payments', [OrderPaymentController::class, 'store'])->name('orders.payments.store');

    Route::get('shopify', [ShopifyController::class, 'index'])->name('shopify.index');
    Route::post('shopify/sync-products', [ShopifyController::class, 'syncProducts'])->name('shopify.sync-products');
    Route::post('shopify/push-inventory', [ShopifyController::class, 'pushInventory'])->name('shopify.push-inventory');
    Route::post('shopify/sync-orders', [ShopifyController::class, 'syncOrders'])->name('shopify.sync-orders');
    Route::get('shopify/connect', [ShopifyOAuthController::class, 'connect'])->name('shopify.connect');
    Route::get('shopify/callback', [ShopifyOAuthController::class, 'callback'])->name('shopify.callback');

    Route::resource('riders', RiderController::class)->except(['show']);
    Route::get('riders/{rider}/manifest', [RiderController::class, 'manifest'])->name('riders.manifest');
    Route::get('riders/{rider}/manifest/excel', [RiderController::class, 'manifestExcel'])->name('riders.manifest.excel');
    Route::get('riders-report', [RiderController::class, 'report'])->name('riders.report');
    Route::post('riders/{rider}/check-in', [RiderController::class, 'checkIn'])->name('riders.check-in');
    Route::post('riders/{rider}/check-out', [RiderController::class, 'checkOut'])->name('riders.check-out');
    Route::post('riders/{rider}/deactivate', [RiderController::class, 'deactivate'])->name('riders.deactivate');
    Route::get('riders/{rider}/wallet', [RiderAccountController::class, 'show'])->name('riders.wallet');
    Route::get('riders/{rider}/wallet/orders/{order}', [RiderAccountController::class, 'showOrderAttempts'])->name('riders.wallet.order-attempts');
    Route::get('riders/{rider}/wallet/trips/{trip}', [RiderAccountController::class, 'showTrip'])->name('riders.wallet.trip');
    Route::post('riders/{rider}/wallet/deposit-cash', [RiderWalletController::class, 'recordCashDeposit'])->name('riders.wallet.deposit-cash');
    Route::post('riders/{rider}/wallet/pay-earnings', [RiderWalletController::class, 'payRider'])->name('riders.wallet.pay-earnings');
    Route::post('riders/{rider}/wallet/adjust', [RiderWalletController::class, 'adjust'])->name('riders.wallet.adjust');

    Route::get('dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
    Route::post('dispatch/{order}/assign', [DispatchController::class, 'assign'])->name('dispatch.assign');
    Route::post('dispatch/{order}/unassign', [DispatchController::class, 'unassign'])->name('dispatch.unassign');
    Route::post('dispatch/{order}/instructions', [DispatchController::class, 'updateInstructions'])->name('dispatch.instructions');
    Route::post('dispatch/assign/bulk', [DispatchController::class, 'bulkAssign'])->name('dispatch.assign.bulk');
    Route::post('dispatch/{order}/picked-up', [DispatchController::class, 'pickedUp'])->name('dispatch.picked-up');
    Route::post('dispatch/{order}/out-for-delivery', [DispatchController::class, 'outForDelivery'])->name('dispatch.out-for-delivery');
    Route::post('dispatch/picked-up/bulk', [DispatchController::class, 'bulkPickedUp'])->name('dispatch.picked-up.bulk');
    Route::post('dispatch/out-for-delivery/bulk', [DispatchController::class, 'bulkOutForDelivery'])->name('dispatch.out-for-delivery.bulk');
    Route::post('dispatch/{order}/delivered', [DispatchController::class, 'delivered'])->name('dispatch.delivered');
    Route::post('dispatch/{order}/failed', [DispatchController::class, 'failed'])->name('dispatch.failed');
    Route::post('dispatch/{order}/returned', [DispatchController::class, 'returned'])->name('dispatch.returned');

    Route::resource('employees', EmployeeController::class);

    Route::get('advances', [EmployeeAdvanceController::class, 'index'])->name('advances.index');
    Route::post('advances', [EmployeeAdvanceController::class, 'store'])->name('advances.store');
    Route::post('advances/{advance}/write-off', [EmployeeAdvanceController::class, 'writeOff'])->name('advances.write-off');

    Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('payroll/adjustments', [PayrollController::class, 'adjustmentsIndex'])->name('payroll.adjustments.index');
    Route::get('payroll/{payrollRun}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::post('payroll/{payrollRun}/approve', [PayrollController::class, 'approve'])->name('payroll.approve');
    Route::post('payroll/{payrollRun}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
    Route::post('payroll/items/{payrollRunItem}/adjustments', [PayrollController::class, 'storeAdjustment'])->name('payroll.adjustments.store');
    Route::delete('payroll/adjustments/{adjustment}', [PayrollController::class, 'destroyAdjustment'])->name('payroll.adjustments.destroy');

    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::resource('accounts', AccountController::class)->except(['show']);

    Route::get('accounting/mapping', [AccountMappingController::class, 'edit'])->name('accounting.mapping.edit');
    Route::put('accounting/mapping', [AccountMappingController::class, 'update'])->name('accounting.mapping.update');

    Route::get('vouchers/{type}/create', [VoucherController::class, 'create'])->name('vouchers.create');
    Route::post('vouchers/{type}', [VoucherController::class, 'store'])->name('vouchers.store');

    Route::get('journal-entries', [JournalEntryController::class, 'index'])->name('journal-entries.index');
    Route::get('journal-entries/create', [JournalEntryController::class, 'create'])->name('journal-entries.create');
    Route::post('journal-entries', [JournalEntryController::class, 'store'])->name('journal-entries.store');
    Route::get('journal-entries/{journalEntry}', [JournalEntryController::class, 'show'])->name('journal-entries.show');
    Route::post('journal-entries/{journalEntry}/void', [JournalEntryController::class, 'void'])->name('journal-entries.void');

    Route::get('reports/ledger', [AccountingReportController::class, 'ledger'])->name('reports.ledger');
    Route::get('reports/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('reports.trial-balance');
    Route::get('reports/profit-and-loss', [AccountingReportController::class, 'profitAndLoss'])->name('reports.profit-and-loss');
    Route::get('reports/receivables-aging', [AccountingReportController::class, 'receivablesAging'])->name('reports.receivables-aging');
    Route::get('reports/payables-aging', [AccountingReportController::class, 'payablesAging'])->name('reports.payables-aging');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('settings/inventory', [SettingsController::class, 'updateInventory'])->name('settings.inventory.update');
});
