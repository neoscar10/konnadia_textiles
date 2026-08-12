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
        // 1. Add unconverted_quantity and converted_quantity to production_batches
        if (!Schema::hasColumn('production_batches', 'unconverted_quantity')) {
            Schema::table('production_batches', function (Blueprint $table) {
                $afterCol = Schema::hasColumn('production_batches', 'total_finished_quantity')
                    ? 'total_finished_quantity'
                    : (Schema::hasColumn('production_batches', 'planned_quantity') ? 'planned_quantity' : null);

                if ($afterCol) {
                    $table->integer('unconverted_quantity')->default(0)->after($afterCol);
                } else {
                    $table->integer('unconverted_quantity')->default(0);
                }
                $table->integer('converted_quantity')->default(0)->after('unconverted_quantity');
            });
        }

        // 2. Create storefront_product_bundles table
        if (!Schema::hasTable('storefront_product_bundles')) {
            Schema::create('storefront_product_bundles', function (Blueprint $table) {
                $table->id();
                $table->string('bundle_code')->unique();
                $table->foreignId('product_id')->nullable()->constrained('products', 'id', 'spb_product_fk')->nullOnDelete();
                $table->foreignId('product_combination_id')->nullable()->constrained('product_combinations', 'id', 'spb_comb_fk')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users', 'id', 'spb_user_fk')->nullOnDelete();
                $table->integer('quantity_created')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create storefront_product_bundle_items table
        if (!Schema::hasTable('storefront_product_bundle_items')) {
            Schema::create('storefront_product_bundle_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('storefront_product_bundle_id')->constrained('storefront_product_bundles', 'id', 'spbi_bundle_fk')->onDelete('cascade');
                $table->foreignId('production_batch_id')->nullable()->constrained('production_batches', 'id', 'spbi_batch_fk')->nullOnDelete();
                $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products', 'id', 'spbi_mfg_prod_fk')->onDelete('cascade');
                $table->integer('quantity_used')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storefront_product_bundle_items');
        Schema::dropIfExists('storefront_product_bundles');

        if (Schema::hasTable('production_batches') && Schema::hasColumn('production_batches', 'unconverted_quantity')) {
            Schema::table('production_batches', function (Blueprint $table) {
                $table->dropColumn(['unconverted_quantity', 'converted_quantity']);
            });
        }
    }
};
