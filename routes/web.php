<?php

use Illuminate\Support\Facades\Route;

// Landing Page and B2B Home redirected to DashboardPage
// Defined under the optional_customer group below

// Unified Login Page
Route::middleware('guest')->group(function () {
    Route::get('/login', \App\Livewire\Auth\LoginPage::class)->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Redirect legacy admin login to unified login
Route::redirect('/admin/login', '/login')->name('admin.login');

// Admin Portal Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Protected Admin Routes (requires auth and super_admin or admin role)
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('dashboard', \App\Livewire\Admin\DashboardPage::class)->name('dashboard');
        
        Route::get('customers', \App\Livewire\Admin\Customers\CustomerIndexPage::class)->name('customers.index');
        Route::get('customer-levels', \App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)->name('customer-levels.index');
        
        Route::get('categories', \App\Livewire\Admin\Categories\CategoryIndexPage::class)->name('categories.index');
        Route::redirect('sub-categories', 'categories');
        
        Route::get('variant-parameters', \App\Livewire\Admin\Variants\VariantParameterIndexPage::class)->name('variant-parameters.index');
        Route::get('variant-values', \App\Livewire\Admin\Variants\VariantValueIndexPage::class)->name('variant-values.index');
        
        Route::get('products', \App\Livewire\Admin\Products\ProductIndexPage::class)->name('products.index');
        Route::get('products/details', \App\Livewire\Admin\Products\ProductShowPage::class)->name('products.show');
        Route::get('design-catalog', \App\Livewire\Admin\Products\DesignCatalogPage::class)->name('design-catalog.index');
        Route::get('tags', \App\Livewire\Admin\Tags\TagIndexPage::class)->name('tags.index');
        Route::get('units', \App\Livewire\Admin\Units\UnitIndexPage::class)->name('units.index');
        Route::get('inventory', \App\Livewire\Admin\Inventory\InventoryIndexPage::class)->name('inventory.index');
        Route::get('pricing', \App\Livewire\Admin\Pricing\PricingIndexPage::class)->name('pricing.index');
        
        Route::get('orders', \App\Livewire\Admin\Orders\OrderIndexPage::class)->name('orders.index');
        Route::get('orders/{orderNumber}', \App\Livewire\Admin\Orders\OrderShowPage::class)->name('orders.show');
        Route::get('credit-management', \App\Livewire\Admin\Credit\CreditManagementPage::class)->name('credit-management.index');
        
        Route::get('reports', \App\Livewire\Admin\Reports\ReportIndexPage::class)->name('reports.index');
        Route::get('notifications', \App\Livewire\Admin\Notifications\NotificationIndexPage::class)->name('notifications.index');
        Route::get('home-content', \App\Livewire\Admin\HomeContent\HomeContentPage::class)->name('home-content.index');
        Route::get('settings', \App\Livewire\Admin\Settings\SettingsPage::class)->name('settings.index');
        Route::get('admins', \App\Livewire\Admin\Admins\AdminIndexPage::class)->name('admins.index');
        Route::get('support', \App\Livewire\Admin\Support\SupportPage::class)->name('support.index');
        
        // Retail Transfers System
        Route::get('retail-shops', \App\Livewire\Admin\RetailShops\RetailShopIndexPage::class)->name('retail-shops.index');
        Route::get('product-transfers', \App\Livewire\Admin\ProductTransfers\ProductTransferIndexPage::class)->name('product-transfers.index');
        Route::get('product-transfers/{id}/pdf', function ($id) {
            $transfer = \App\Models\ProductTransfer::findOrFail($id);
            return app(\App\Services\StockTransfer\TransferDocumentService::class)->download($transfer);
        })->name('product-transfers.pdf');
        Route::get('order-dispatches/{dispatchNumber}/pdf', function ($dispatchNumber) {
            return app(\App\Services\Order\DispatchDocumentService::class)->download($dispatchNumber);
        })->name('order-dispatches.pdf');
    });
});

// Optional Customer Route Group (available to guests and logged-in customers)
Route::middleware(['optional_customer'])->group(function () {
    Route::get('/', \App\Livewire\Customer\DashboardPage::class)->name('home');
    Route::redirect('/home', '/');

    Route::prefix('portal')->group(function () {
        Route::get('products/search-suggestions', function (\Illuminate\Http\Request $request) {
            $q = $request->query('q', '');
            if (strlen($q) < 2) {
                return response()->json([]);
            }
            $products = \App\Models\Product::where('is_active', true)
                ->where(function($query) use ($q) {
                    $query->where('title', 'like', "%{$q}%")
                          ->orWhere('sku', 'like', "%{$q}%");
                })
                ->with(['primaryMedia'])
                ->limit(6)
                ->get();
            $results = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                    'base_price' => auth()->check() ? (float)$product->base_price : null,
                    'image' => $product->primaryMedia ? asset('storage/' . $product->primaryMedia->file_path) : null,
                    'url' => route('customer.products.show', ['slug' => $product->slug]),
                ];
            });
            return response()->json($results);
        })->name('customer.products.suggestions');

        Route::get('categories', \App\Livewire\Customer\Categories\CategoryIndexPage::class)->name('customer.categories.index');
        Route::get('categories/{slug}', \App\Livewire\Customer\Categories\CategoryShowPage::class)->name('customer.categories.show');
        Route::get('products', \App\Livewire\Customer\Products\ProductIndexPage::class)->name('customer.products.index');
        Route::get('products/{slug}', \App\Livewire\Customer\Products\ProductShowPage::class)->name('customer.products.show');
    });
});

// Customer Portal Routes (Requires Authentication & Customer Role)
Route::middleware(['auth', 'customer'])->prefix('portal')->group(function () {
    Route::get('dashboard', \App\Livewire\Customer\DashboardPage::class)->name('customer.dashboard');
    Route::get('cart', \App\Livewire\Customer\Cart\CartPage::class)->name('customer.cart.index');
    Route::get('cart/saved', \App\Livewire\Customer\Cart\SavedCartsPage::class)->name('customer.cart.saved');
    Route::get('order/review', \App\Livewire\Customer\Orders\OrderReviewPage::class)->name('customer.orders.review');
    Route::get('order/success', \App\Livewire\Customer\Orders\OrderSuccessPage::class)->name('customer.orders.success');
    Route::get('orders', \App\Livewire\Customer\Orders\OrderIndexPage::class)->name('customer.orders.index');
    Route::get('orders/{orderNumber}', \App\Livewire\Customer\Orders\OrderShowPage::class)->name('customer.orders.show');
    Route::get('notifications', \App\Livewire\Customer\Notifications\NotificationPage::class)->name('customer.notifications.index');
    Route::get('profile', \App\Livewire\Customer\Profile\ProfilePage::class)->name('customer.profile.show');
    Route::get('change-password', function () { return redirect()->route('customer.profile.show'); })->name('customer.profile.change-password');
});

Route::redirect('/customer/dashboard', '/portal/dashboard');
Route::redirect('/customer/orders', '/portal/orders');
Route::redirect('/customer/products', '/portal/products');
Route::redirect('/customer/cart', '/portal/cart');
Route::redirect('/customer/profile', '/portal/profile');

