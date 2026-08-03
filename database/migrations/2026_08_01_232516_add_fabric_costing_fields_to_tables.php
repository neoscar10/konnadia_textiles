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
            $table->boolean('is_fabric_used')->default(false);
            $table->decimal('standard_fabric_width', 10, 4)->nullable();
            $table->decimal('standard_fabric_length', 10, 4)->nullable();
            $table->string('fabric_width_unit')->nullable();
            $table->string('fabric_length_unit')->nullable();
        });

        Schema::table('job_material_consumptions', function (Blueprint $table) {
            $table->decimal('consumed_length', 10, 4)->nullable();
            $table->decimal('calculated_base_cost', 12, 4)->nullable();
            $table->decimal('allocated_wastage_cost', 12, 4)->nullable();
            $table->decimal('total_fabric_cost', 12, 4)->nullable();
        });

        Schema::table('job_production_outputs', function (Blueprint $table) {
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->decimal('fabric_width', 10, 4)->nullable();
            $table->decimal('fabric_length', 10, 4)->nullable();
            $table->string('fabric_width_unit')->nullable();
            $table->string('fabric_length_unit')->nullable();
            $table->decimal('calculated_base_cost', 12, 4)->nullable();
            $table->decimal('allocated_wastage_cost', 12, 4)->nullable();
            $table->decimal('total_fabric_cost', 12, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_production_outputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_batch_id');
            $table->dropColumn([
                'fabric_width',
                'fabric_length',
                'fabric_width_unit',
                'fabric_length_unit',
                'calculated_base_cost',
                'allocated_wastage_cost',
                'total_fabric_cost'
            ]);
        });

        Schema::table('job_material_consumptions', function (Blueprint $table) {
            $table->dropColumn([
                'consumed_length',
                'calculated_base_cost',
                'allocated_wastage_cost',
                'total_fabric_cost'
            ]);
        });

        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropColumn([
                'is_fabric_used',
                'standard_fabric_width',
                'standard_fabric_length',
                'fabric_width_unit',
                'fabric_length_unit'
            ]);
        });
    }
};
