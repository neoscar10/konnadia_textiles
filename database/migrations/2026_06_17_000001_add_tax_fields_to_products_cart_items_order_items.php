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
        // Add HSN code and GST percentage to products table
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('title');
            }
            if (!Schema::hasColumn('products', 'gst_percentage')) {
                $table->decimal('gst_percentage', 5, 2)->nullable()->after('hsn_code');
            }
        });

        // Add HSN code snapshot to cart_items table
        Schema::table('cart_items', function (Blueprint $table) {
            if (!Schema::hasColumn('cart_items', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('selected_options');
            }
        });

        // Add HSN code snapshot to order_items table
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('product_sku');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['hsn_code', 'gst_percentage']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            if (Schema::hasColumn('cart_items', 'hsn_code')) {
                $table->dropColumn('hsn_code');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'hsn_code')) {
                $table->dropColumn('hsn_code');
            }
        });
    }
};
