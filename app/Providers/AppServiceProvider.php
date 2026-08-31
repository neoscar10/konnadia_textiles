<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "super_admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Register Stock Replenishment Observers
        \App\Models\Product::observe(\App\Observers\ProductStockObserver::class);
        \App\Models\ProductCombination::observe(\App\Observers\ProductCombinationStockObserver::class);
    }
}
