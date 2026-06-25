<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make stock_quantity nullable on both products and product_combinations tables.
     * null = N/A / Unlimited — no stock restriction applies.
     * 0   = Explicitly out of stock.
     * n   = Tracked quantity.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable()->default(null)->change();
        });

        Schema::table('product_combinations', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable(false)->default(0)->change();
        });

        Schema::table('product_combinations', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable(false)->default(0)->change();
        });
    }
};
