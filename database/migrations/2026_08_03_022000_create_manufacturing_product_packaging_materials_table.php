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
        Schema::create('manufacturing_product_packaging_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_product_id')
                ->constrained('manufacturing_products')
                ->onDelete('cascade')
                ->name('mp_pkg_mat_product_fk');
            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->onDelete('cascade')
                ->name('mp_pkg_mat_raw_fk');
            $table->decimal('required_quantity', 10, 4)->default(1.0000);
            $table->timestamps();
        });

        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->boolean('is_packaging_used')->default(false)->after('is_stitching_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropColumn('is_packaging_used');
        });
        
        Schema::dropIfExists('manufacturing_product_packaging_materials');
    }
};
