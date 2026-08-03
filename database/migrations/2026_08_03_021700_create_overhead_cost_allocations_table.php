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
        Schema::create('overhead_cost_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->onDelete('cascade');
            $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
            $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->onDelete('cascade');
            $table->decimal('allocated_quantity', 10, 4);
            $table->decimal('allocated_cost', 10, 2);
            $table->enum('allocation_method', ['direct_batch', 'volume_prorata', 'period_pool']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overhead_cost_allocations');
    }
};
