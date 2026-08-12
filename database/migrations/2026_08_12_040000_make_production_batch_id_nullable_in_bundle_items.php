<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('storefront_product_bundle_items') && Schema::hasColumn('storefront_product_bundle_items', 'production_batch_id')) {
            Schema::table('storefront_product_bundle_items', function (Blueprint $table) {
                $table->unsignedBigInteger('production_batch_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('storefront_product_bundle_items') && Schema::hasColumn('storefront_product_bundle_items', 'production_batch_id')) {
            Schema::table('storefront_product_bundle_items', function (Blueprint $table) {
                $table->unsignedBigInteger('production_batch_id')->nullable(false)->change();
            });
        }
    }
};
