<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\MobileAuthController;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [MobileAuthController::class, 'login']);
    Route::post('/auth/forgot-password', [MobileAuthController::class, 'forgotPassword']);
    Route::post('/auth/otp/send', [\App\Http\Controllers\Api\V1\Auth\OtpAuthController::class, 'send']);
    Route::post('/auth/otp/login', [\App\Http\Controllers\Api\V1\Auth\OtpAuthController::class, 'login']);
    Route::post('/contact', [\App\Http\Controllers\Api\V1\PublicContactController::class, 'submit']);

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
                Route::get('/export', [\App\Http\Controllers\Api\V1\Admin\AdminCustomerController::class, 'export']);
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
            });

            // Design Catalog (Requires 'access design-catalog' permission)
            Route::middleware('api.permission:access design-catalog')->prefix('design-catalog')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminDesignCatalogController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminDesignCatalogController::class, 'options']);
                Route::post('/share', [\App\Http\Controllers\Api\V1\Admin\AdminDesignCatalogController::class, 'share']);
            });

            // Category Management (Requires 'access categories' permission)
            Route::middleware('api.permission:access categories')->prefix('categories')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'index']);
                Route::get('/tree', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'tree']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'toggleStatus']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'destroy']);

                // Category Defaults & Product Management
                Route::get('/{id}/defaults', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'getDefaults']);
                Route::post('/{id}/defaults', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'saveDefaults']);
                Route::post('/{id}/move-products', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'moveProducts']);
                Route::get('/{id}/products', [\App\Http\Controllers\Api\V1\Admin\AdminCategoryController::class, 'getProducts']);
            });

            // Tag Management (Requires 'access tags' permission)
            Route::middleware('api.permission:access tags')->prefix('tags')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'options']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'update']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminTagController::class, 'destroy']);
            });

            // Inventory Management (Requires 'access inventory' permission)
            Route::middleware('api.permission:access inventory')->prefix('inventory')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AdminInventoryController::class, 'stats']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminInventoryController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminInventoryController::class, 'show']);
                Route::post('/adjust', [\App\Http\Controllers\Api\V1\Admin\AdminInventoryController::class, 'adjustStock']);
                Route::post('/products/{id}/variants-stock', [\App\Http\Controllers\Api\V1\Admin\AdminInventoryController::class, 'updateVariantStocks']);
            });

            // Retail Shops Management (Requires 'access retail-shops' permission)
            Route::middleware('api.permission:access retail-shops')->prefix('retail-shops')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'options']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'update']);
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'update']);
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'toggleStatus']);
                Route::post('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'toggleStatus']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminRetailShopController::class, 'destroy']);
            });

            // Product Transfers Management (Requires 'access product-transfers' permission)
            Route::middleware('api.permission:access product-transfers')->prefix('product-transfers')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminProductTransferController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminProductTransferController::class, 'options']);
                Route::get('/products/{id}/transfer-info', [\App\Http\Controllers\Api\V1\Admin\AdminProductTransferController::class, 'productTransferInfo']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminProductTransferController::class, 'show']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminProductTransferController::class, 'store']);
            });

            // Order Management & Fractional Dispatch (Requires 'access orders' permission)
            Route::middleware('api.permission:access orders')->prefix('orders')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'stats']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'index']);
                Route::get('/{idOrNumber}', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'show']);

                // Order Status Transitions
                Route::post('/{id}/mark-under-review', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'markUnderReview']);
                Route::post('/{id}/verify-receipt', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'verifyReceipt']);
                Route::post('/{id}/reject-receipt', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'rejectReceipt']);
                Route::post('/{id}/approve', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'approve']);
                Route::post('/{id}/reject', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'reject']);
                Route::post('/{id}/cancel', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'cancel']);

                // Item & Fractional Dispatch Actions
                Route::post('/items/{itemId}/dispatch', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'dispatchItem']);
                Route::post('/{id}/bulk-dispatch', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'bulkDispatch']);
                Route::post('/items/{itemId}/cancel', [\App\Http\Controllers\Api\V1\Admin\AdminOrderController::class, 'cancelItem']);
            });

            // Home Content CMS (Requires 'access home-content' permission)
            Route::middleware('api.permission:access home-content')->prefix('home-content')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'stats']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'index']);
                Route::get('/options', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'options']);
                Route::post('/upload-media', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'uploadMedia']);
                Route::post('/reorder', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'reorder']);
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'store']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'show'])->where('id', '[0-9]+');
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'update'])->where('id', '[0-9]+');
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'update'])->where('id', '[0-9]+');
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'toggleStatus'])->where('id', '[0-9]+');
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminHomeContentController::class, 'destroy'])->where('id', '[0-9]+');
            });

            // Admin Users Management (Requires 'access admins' permission)
            Route::middleware('api.permission:access admins')->prefix('admins')->group(function () {
                Route::get('/permissions-list', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'permissionsList']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'show'])->where('id', '[0-9]+');
                Route::post('/', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'store']);
                Route::put('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'update'])->where('id', '[0-9]+');
                Route::patch('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'update'])->where('id', '[0-9]+');
                Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'toggleStatus'])->where('id', '[0-9]+');
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminUserManagementController::class, 'destroy'])->where('id', '[0-9]+');
            });

            // System Settings & Password (Authenticated Admin)
            Route::prefix('settings')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminSettingsController::class, 'index']);
                Route::post('/change-password', [\App\Http\Controllers\Api\V1\Admin\AdminSettingsController::class, 'changePassword']);
            });

            // Dashboard Analytics (Authenticated Admin)
            Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Admin\AdminDashboardAnalyticsController::class, 'show']);

            // Units Configuration
            Route::prefix('units')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminUnitController::class, 'index']);
            });

            // Credit Management (Requires 'access customers' or 'access orders' permission)
            Route::prefix('credit-management')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'stats']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'index']);
                Route::get('/customers/{id}/ledger', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'ledger']);
                Route::post('/customers/{id}/limit', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'updateLimit']);
                Route::post('/customers/{id}/payment', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'recordPayment']);
                Route::post('/customers/{id}/adjust', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'adjustOutstanding']);
                Route::patch('/customers/{id}/toggle-beyond-limit', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'toggleBeyondLimit']);
                Route::post('/customers/{id}/hold', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'hold']);
                Route::post('/customers/{id}/release-hold', [\App\Http\Controllers\Api\V1\Admin\AdminCreditManagementController::class, 'releaseHold']);
            });

            // Reports & Business Analytics
            Route::prefix('reports')->group(function () {
                Route::get('/sales', [\App\Http\Controllers\Api\V1\Admin\AdminReportsController::class, 'sales']);
                Route::get('/customers', [\App\Http\Controllers\Api\V1\Admin\AdminReportsController::class, 'customers']);
                Route::get('/inventory', [\App\Http\Controllers\Api\V1\Admin\AdminReportsController::class, 'inventory']);
            });

            // Notifications System
            Route::prefix('notifications')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'index']);
                Route::post('/mark-read', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'markRead']);
                Route::post('/mark-all-read', [\App\Http\Controllers\Api\V1\Admin\AdminNotificationController::class, 'markAllRead']);
            });

            // Support & Contact Messages (Requires 'access contact-messages' permission)
            Route::middleware('api.permission:access contact-messages')->prefix('contact-messages')->group(function () {
                Route::get('/stats', [\App\Http\Controllers\Api\V1\Admin\AdminContactMessageController::class, 'stats']);
                Route::get('/', [\App\Http\Controllers\Api\V1\Admin\AdminContactMessageController::class, 'index']);
                Route::get('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminContactMessageController::class, 'show']);
                Route::patch('/{id}/mark-unread', [\App\Http\Controllers\Api\V1\Admin\AdminContactMessageController::class, 'markUnread']);
                Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Admin\AdminContactMessageController::class, 'destroy']);
            });
        });
    });
});
