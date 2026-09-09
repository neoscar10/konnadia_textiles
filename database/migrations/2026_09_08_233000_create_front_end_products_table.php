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
        // 1. Front-End Products master table
        Schema::create('front_end_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('leaf_category_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Front-End Product Constituent Manufacturing Products (Dynamic rows)
        Schema::create('front_end_product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('front_end_product_id')->constrained('front_end_products', indexName: 'fk_fepc_fep_id')->cascadeOnDelete();
            $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products', indexName: 'fk_fepc_mp_id')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // 3. Front-End Product Required Packaging Materials (Dynamic rows)
        Schema::create('front_end_product_packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('front_end_product_id')->constrained('front_end_products', indexName: 'fk_fepp_fep_id')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials', indexName: 'fk_fepp_rm_id')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });

        // 4. Converted Finished Goods Batches Hub
        Schema::create('finished_goods_batches', function (Blueprint $table) {
            $table->id();
            $table->string('barcode')->unique();
            $table->foreignId('front_end_product_id')->constrained('front_end_products', indexName: 'fk_fgb_fep_id')->cascadeOnDelete();
            $table->string('design_id')->nullable();
            $table->integer('converted_qty')->default(1);
            $table->string('unit')->default('Piece (Pcs)');
            $table->integer('unit_factor')->default(1);
            $table->timestamp('converted_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users', indexName: 'fk_fgb_cb_id')->nullOnDelete();
            $table->json('costing_summary')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Finished Goods Batch Constituent Job / Manufacturing Output Items Deducted
        Schema::create('finished_goods_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_goods_batch_id')->constrained('finished_goods_batches', indexName: 'fk_fgbi_fgb_id')->cascadeOnDelete();
            $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products', indexName: 'fk_fgbi_mp_id')->cascadeOnDelete();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches', indexName: 'fk_fgbi_pb_id')->nullOnDelete();
            $table->foreignId('production_job_id')->nullable()->constrained('production_jobs', indexName: 'fk_fgbi_pj_id')->nullOnDelete();
            $table->integer('quantity_used')->default(0);
            $table->timestamps();
        });

        // 6. Finished Goods Batch Packaging Deducted
        Schema::create('finished_goods_batch_packagings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finished_goods_batch_id')->constrained('finished_goods_batches', indexName: 'fk_fgbp_fgb_id')->cascadeOnDelete();
            $table->foreignId('raw_material_id')->constrained('raw_materials', indexName: 'fk_fgbp_rm_id')->cascadeOnDelete();
            $table->integer('quantity_deducted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finished_goods_batch_packagings');
        Schema::dropIfExists('finished_goods_batch_items');
        Schema::dropIfExists('finished_goods_batches');
        Schema::dropIfExists('front_end_product_packagings');
        Schema::dropIfExists('front_end_product_components');
        Schema::dropIfExists('front_end_products');
    }
};
