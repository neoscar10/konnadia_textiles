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
            $table->boolean('is_stitching_used')->default(false);
        });

        Schema::create('manufacturing_product_stitching_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_product_id')
                ->constrained('manufacturing_products')
                ->onDelete('cascade')
                ->name('mp_stitch_mat_product_fk');
            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->onDelete('cascade')
                ->name('mp_stitch_mat_raw_fk');
            $table->timestamps();
        });

        Schema::create('stitching_cost_pools', function (Blueprint $table) {
            $table->id();
            $table->string('period_name');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_stitching_cost', 12, 2)->default(0.00);
            $table->string('status')->default('open'); // 'open', 'allocated', 'closed'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stitching_cost_pools');
        Schema::dropIfExists('manufacturing_product_stitching_materials');

        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropColumn('is_stitching_used');
        });
    }
};
