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
    });
});
