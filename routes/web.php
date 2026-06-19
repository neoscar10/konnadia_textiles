<?php

use Illuminate\Support\Facades\Route;

// Redirect root to home
Route::get('/', function () {
    return redirect()->route('home');
});

// Public Landing Page
Route::view('/home', 'home')->name('home');

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
        Route::get('units', \App\Livewire\Admin\Units\UnitIndexPage::class)->name('units.index');
        Route::get('inventory', \App\Livewire\Admin\Inventory\InventoryIndexPage::class)->name('inventory.index');
        Route::get('pricing', \App\Livewire\Admin\Pricing\PricingIndexPage::class)->name('pricing.index');
        
        Route::get('orders', \App\Livewire\Admin\Orders\OrderIndexPage::class)->name('orders.index');
        Route::get('orders/{orderNumber}', \App\Livewire\Admin\Orders\OrderShowPage::class)->name('orders.show');
        Route::get('credit-management', \App\Livewire\Admin\Credit\CreditManagementPage::class)->name('credit-management.index');
        
        Route::get('reports', \App\Livewire\Admin\Reports\ReportIndexPage::class)->name('reports.index');
        Route::get('notifications', \App\Livewire\Admin\Notifications\NotificationIndexPage::class)->name('notifications.index');
        Route::get('settings', \App\Livewire\Admin\Settings\SettingsPage::class)->name('settings.index');
        Route::get('support', \App\Livewire\Admin\Support\SupportPage::class)->name('support.index');
    });
});

// Customer Portal Routes
Route::middleware(['auth', 'customer'])->prefix('portal')->group(function () {
    Route::get('dashboard', \App\Livewire\Customer\DashboardPage::class)->name('customer.dashboard');
    Route::get('categories', \App\Livewire\Customer\Categories\CategoryIndexPage::class)->name('customer.categories.index');
    Route::get('categories/{slug}', \App\Livewire\Customer\Categories\CategoryShowPage::class)->name('customer.categories.show');
    Route::get('products', \App\Livewire\Customer\Products\ProductIndexPage::class)->name('customer.products.index');
    Route::get('products/{slug}', \App\Livewire\Customer\Products\ProductShowPage::class)->name('customer.products.show');
    Route::get('cart', \App\Livewire\Customer\Cart\CartPage::class)->name('customer.cart.index');
    Route::get('cart/saved', \App\Livewire\Customer\Cart\SavedCartsPage::class)->name('customer.cart.saved');
    Route::get('order/review', \App\Livewire\Customer\Orders\OrderReviewPage::class)->name('customer.orders.review');
    Route::get('order/success', \App\Livewire\Customer\Orders\OrderSuccessPage::class)->name('customer.orders.success');
    Route::get('orders', \App\Livewire\Customer\Orders\OrderIndexPage::class)->name('customer.orders.index');
    Route::get('orders/{orderNumber}', \App\Livewire\Customer\Orders\OrderShowPage::class)->name('customer.orders.show');
    Route::get('notifications', \App\Livewire\Customer\Notifications\NotificationPage::class)->name('customer.notifications.index');
    Route::get('profile', \App\Livewire\Customer\Profile\ProfilePage::class)->name('customer.profile.show');
    Route::get('change-password', \App\Livewire\Customer\Profile\ChangePasswordPage::class)->name('customer.profile.change-password');
});

Route::redirect('/customer/dashboard', '/portal/dashboard');
Route::redirect('/customer/orders', '/portal/orders');
Route::redirect('/customer/products', '/portal/products');
Route::redirect('/customer/cart', '/portal/cart');
Route::redirect('/customer/profile', '/portal/profile');


// Fallback route to serve files from storage when symlinks are disabled on the server
Route::get('/storage-files/{path}', function ($path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    
    if (!$disk->exists($path)) {
        abort(404);
    }
    
    return response()->file($disk->path($path));
})->where('path', '.*');
