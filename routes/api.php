<?php

use App\Http\Controllers\Api\Rider\AuthController as RiderAuthController;
use App\Http\Controllers\Api\Rider\DeliveryController as RiderDeliveryController;
use App\Http\Controllers\Api\Rider\DeviceTokenController;
use App\Http\Controllers\Api\Rider\LocationController;
use App\Http\Controllers\Api\Rider\RiderStatusController;
use App\Http\Controllers\Api\Rider\WalletController as RiderWalletApiController;
use App\Http\Controllers\ShopifyWebhookController;
use Illuminate\Support\Facades\Route;

// Shopify webhook receivers. All order-related topics (create/updated/cancelled/
// fulfilled) route to the same handler — ShopifyOrderSyncService reads whatever
// fields are present in the payload (e.g. cancelled_at) rather than branching
// on topic name.
Route::middleware('shopify.webhook')->prefix('webhooks/shopify')->group(function () {
    Route::post('orders/create', [ShopifyWebhookController::class, 'order'])->name('webhooks.shopify.orders.create');
    Route::post('orders/updated', [ShopifyWebhookController::class, 'order'])->name('webhooks.shopify.orders.updated');
    Route::post('orders/cancelled', [ShopifyWebhookController::class, 'order'])->name('webhooks.shopify.orders.cancelled');
    Route::post('orders/fulfilled', [ShopifyWebhookController::class, 'order'])->name('webhooks.shopify.orders.fulfilled');
});

// Rider mobile app API. Token-based (Sanctum personal access tokens, not
// session cookies) — login exchanges credentials for a token scoped to the
// 'rider' ability; every other route requires that token plus the 'rider'
// middleware, which additionally checks the authenticated user actually has
// a rider_profiles row (see EnsureIsRider).
Route::prefix('v1/rider')->name('api.rider.')->middleware('log.rider-app')->group(function () {
    Route::post('login', [RiderAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login');

    Route::middleware(['auth:sanctum', 'rider'])->group(function () {
        Route::post('logout', [RiderAuthController::class, 'logout'])->name('logout');

        Route::get('deliveries', [RiderDeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{order}', [RiderDeliveryController::class, 'show'])->name('deliveries.show');
        Route::post('deliveries/{order}/picked-up', [RiderDeliveryController::class, 'pickedUp'])->name('deliveries.picked-up');
        Route::post('deliveries/{order}/out-for-delivery', [RiderDeliveryController::class, 'outForDelivery'])->name('deliveries.out-for-delivery');
        Route::post('deliveries/picked-up/bulk', [RiderDeliveryController::class, 'bulkPickedUp'])->name('deliveries.picked-up.bulk');
        Route::post('deliveries/out-for-delivery/bulk', [RiderDeliveryController::class, 'bulkOutForDelivery'])->name('deliveries.out-for-delivery.bulk');
        Route::post('deliveries/{order}/delivered', [RiderDeliveryController::class, 'delivered'])->name('deliveries.delivered');
        Route::post('deliveries/{order}/failed', [RiderDeliveryController::class, 'failed'])->name('deliveries.failed');
        Route::post('deliveries/{order}/returned', [RiderDeliveryController::class, 'returned'])->name('deliveries.returned');
        Route::post('deliveries/checkout', [RiderDeliveryController::class, 'checkout'])->name('deliveries.checkout');

        Route::get('wallet', [RiderWalletApiController::class, 'show'])->name('wallet');

        Route::post('location', [LocationController::class, 'store'])->name('location.store');
        Route::post('device-token', [DeviceTokenController::class, 'store'])->name('device-token.store');

        Route::get('dashboard', [RiderStatusController::class, 'dashboard'])->name('dashboard');
        Route::post('online', [RiderStatusController::class, 'online'])->name('online');
        Route::post('offline', [RiderStatusController::class, 'offline'])->name('offline');
        Route::post('check-in', [RiderStatusController::class, 'checkIn'])->name('check-in');
        Route::post('check-out', [RiderStatusController::class, 'checkOut'])->name('check-out');
    });
});

// Versioned mobile API routes (/api/v1/...) for the customer app will be
// added under their own prefix in a later phase.
