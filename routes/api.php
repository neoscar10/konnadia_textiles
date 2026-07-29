<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\MobileAuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login']);
    Route::post('/auth/forgot-password', [MobileAuthController::class, 'forgotPassword']);
    Route::post('/auth/otp/send', [\App\Http\Controllers\Api\V1\Auth\OtpAuthController::class, 'send']);
    Route::post('/auth/otp/login', [\App\Http\Controllers\Api\V1\Auth\OtpAuthController::class, 'login']);

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
            Route::get('/home', [\App\Http\Controllers\Api\V1\HomeContentController::class, 'index']);
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

    // ==========================================
    // ADMIN DASHBOARD & MANAGEMENT API ENDPOINTS
    // ==========================================
    Route::prefix('admin')->group(function () {
        // Unauthenticated Admin Auth
        Route::post('/auth/login', [\App\Http\Controllers\Api\V1\Admin\AdminAuthController::class, 'login']);

        // Authenticated Admin Endpoints
        Route::middleware(['auth:api', 'api.admin'])->group(function () {
            // Auth profile & token management
            Route::get('/auth/me', [\App\Http\Controllers\Api\V1\Admin\AdminAuthController::class, 'me']);
            Route::post('/auth/refresh', [\App\Http\Controllers\Api\V1\Admin\AdminAuthController::class, 'refresh']);
            Route::post('/auth/logout', [\App\Http\Controllers\Api\V1\Admin\AdminAuthController::class, 'logout']);

            // Admins Management (Restricted to Super Admin)
            Route::middleware('role:super_admin')->prefix('admins')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'index']);
                Route::get('/permissions', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'permissions']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'toggleStatus']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminManagementController::class, 'destroy']);
            });

            // Customer Management (Requires 'access customers' permission)
            Route::middleware('api.permission:access customers')->prefix('customers')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'toggleStatus']);
                Route::post('/{id}/reset-password', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'resetPassword']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'destroy']);
            });

            // Customer Levels & Discount Tiers (Requires 'access customer-levels' permission)
            Route::middleware('api.permission:access customer-levels')->prefix('customer-levels')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'toggleStatus']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerLevelController::class, 'destroy']);
            });

            // Product Management (Requires 'access products' permission)
            Route::middleware('api.permission:access products')->prefix('products')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'options']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'toggleStatus']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'destroy']);

                // Media Management
                Route::post('/{id}/media', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'uploadMedia']);
                Route::patch('/{id}/media/{media_id}/primary', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'setPrimaryMedia']);
                Route::delete('/{id}/media/{media_id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'deleteMedia']);

                // Manufacturing Task Routing Management
                Route::get('/{id}/routing', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'getRouting']);
                Route::post('/{id}/routing', [\App\Http\Controllers\Api\V1\Admin\AdminProductController::class, 'saveRouting']);
            });
        });
    });
});
