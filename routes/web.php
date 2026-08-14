<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\RiderController;
use App\Http\Controllers\RiderWalletController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockBalanceController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\VendorPaymentController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
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

    Route::resource('orders', OrderController::class)->only(['index', 'show']);
    Route::post('orders/{order}/confirm', [OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('shopify', [ShopifyController::class, 'index'])->name('shopify.index');
    Route::post('shopify/sync-products', [ShopifyController::class, 'syncProducts'])->name('shopify.sync-products');
    Route::post('shopify/push-inventory', [ShopifyController::class, 'pushInventory'])->name('shopify.push-inventory');
    Route::post('shopify/sync-orders', [ShopifyController::class, 'syncOrders'])->name('shopify.sync-orders');

    Route::resource('riders', RiderController::class)->except(['show']);
    Route::get('riders/{rider}/wallet', [RiderWalletController::class, 'show'])->name('riders.wallet');
    Route::post('riders/{rider}/wallet/settle-cod', [RiderWalletController::class, 'settleCod'])->name('riders.wallet.settle-cod');
    Route::post('riders/{rider}/wallet/pay-earnings', [RiderWalletController::class, 'payEarnings'])->name('riders.wallet.pay-earnings');
    Route::post('riders/{rider}/wallet/adjust', [RiderWalletController::class, 'adjust'])->name('riders.wallet.adjust');

    Route::get('dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
    Route::post('dispatch/{order}/assign', [DispatchController::class, 'assign'])->name('dispatch.assign');
    Route::post('dispatch/{order}/picked-up', [DispatchController::class, 'pickedUp'])->name('dispatch.picked-up');
    Route::post('dispatch/{order}/out-for-delivery', [DispatchController::class, 'outForDelivery'])->name('dispatch.out-for-delivery');
    Route::post('dispatch/picked-up/bulk', [DispatchController::class, 'bulkPickedUp'])->name('dispatch.picked-up.bulk');
    Route::post('dispatch/out-for-delivery/bulk', [DispatchController::class, 'bulkOutForDelivery'])->name('dispatch.out-for-delivery.bulk');
    Route::post('dispatch/{order}/delivered', [DispatchController::class, 'delivered'])->name('dispatch.delivered');
    Route::post('dispatch/{order}/failed', [DispatchController::class, 'failed'])->name('dispatch.failed');
    Route::post('dispatch/{order}/returned', [DispatchController::class, 'returned'])->name('dispatch.returned');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('settings/inventory', [SettingsController::class, 'updateInventory'])->name('settings.inventory.update');
});
