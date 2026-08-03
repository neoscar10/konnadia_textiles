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
            $table->boolean('is_subsidiary_used')->default(false);
        });

        Schema::create('manufacturing_product_subsidiary_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_product_id')
                ->constrained('manufacturing_products')
                ->onDelete('cascade')
                ->name('mp_sub_mat_product_fk');
            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->onDelete('cascade')
                ->name('mp_sub_mat_raw_fk');
            $table->decimal('consumption_quantity', 10, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_product_subsidiary_materials');

        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropColumn('is_subsidiary_used');
        });
    }
};
