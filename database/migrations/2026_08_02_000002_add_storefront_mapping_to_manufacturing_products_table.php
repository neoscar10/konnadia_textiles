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
        Schema::table('manufacturing_products', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_products', 'product_id')) {
                $table->foreignId('product_id')->nullable()->after('manufacturing_product_category_id')->constrained('products')->onDelete('set null');
            }
            if (!Schema::hasColumn('manufacturing_products', 'product_combination_id')) {
                $table->foreignId('product_combination_id')->nullable()->after('product_id')->constrained('product_combinations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
            $table->dropForeign(['product_combination_id']);
            $table->dropColumn('product_combination_id');
        });
    }
};
