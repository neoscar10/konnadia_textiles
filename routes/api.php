<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\MobileAuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login']);
    Route::post('/auth/forgot-password', [MobileAuthController::class, 'forgotPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/auth/me', [MobileAuthController::class, 'me']);
        Route::post('/auth/logout', [MobileAuthController::class, 'logout']);
        Route::post('/auth/refresh', [MobileAuthController::class, 'refresh']);
        Route::post('/auth/change-password', [MobileAuthController::class, 'changePassword']);

        // Product Catalog API
        Route::get('/products', [\App\Http\Controllers\Api\V1\ProductCatalogController::class, 'index']);
        Route::get('/products/filters', [\App\Http\Controllers\Api\V1\ProductCatalogController::class, 'filters']);
        Route::get('/products/{product}', [\App\Http\Controllers\Api\V1\ProductCatalogController::class, 'show']);
        Route::get('/products/{product}/related', [\App\Http\Controllers\Api\V1\ProductCatalogController::class, 'related']);

        Route::middleware('api.customer')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\CustomerDashboardController::class, 'show']);

            Route::get('/cart', [\App\Http\Controllers\Api\V1\CartController::class, 'show']);
            Route::post('/cart/items', [\App\Http\Controllers\Api\V1\CartController::class, 'addItem']);
            Route::patch('/cart/items/{cartItem}', [\App\Http\Controllers\Api\V1\CartController::class, 'updateItem']);
            Route::delete('/cart/items/{cartItem}', [\App\Http\Controllers\Api\V1\CartController::class, 'removeItem']);
            Route::delete('/cart', [\App\Http\Controllers\Api\V1\CartController::class, 'clear']);

            Route::get('/checkout/summary', [\App\Http\Controllers\Api\V1\CheckoutController::class, 'summary']);
            Route::post('/checkout/submit', [\App\Http\Controllers\Api\V1\CheckoutController::class, 'submit']);

            Route::get('/orders', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'index']);
            Route::get('/orders/summary', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'summary']);
            Route::get('/orders/filters', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'filters']);
            Route::get('/orders/{order}', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'show']);
            Route::get('/orders/{order}/timeline', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'timeline']);
            Route::get('/orders/{order}/receipt', [\App\Http\Controllers\Api\V1\CustomerOrderController::class, 'receipt']);
        });
    });
});
