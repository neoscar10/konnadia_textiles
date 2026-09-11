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
        // 1. Make raw_material_id nullable on inventory_batches if needed
        Schema::table('inventory_batches', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_batches', 'raw_material_id')) {
                $table->foreignId('raw_material_id')->nullable()->change();
            }
        });

        // 2. Create inventory_bale_items table
        if (!Schema::hasTable('inventory_bale_items')) {
            Schema::create('inventory_bale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_bale_id')->constrained('inventory_bales')->onDelete('cascade');
                $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
                $table->string('item_name')->nullable();
                $table->string('design_number')->nullable();
                $table->string('stock_id')->nullable();
                $table->decimal('declared_length', 12, 4)->default(0.0000);
                $table->decimal('cost_per_unit', 10, 2)->nullable();
                $table->decimal('total_cost', 12, 2)->nullable();
                $table->string('photo_path')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_bale_items');
    }
};
