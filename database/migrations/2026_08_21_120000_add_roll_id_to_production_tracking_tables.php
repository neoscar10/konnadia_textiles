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
        Schema::table('job_production_outputs', function (Blueprint $table) {
            $table->foreignId('inventory_bale_roll_id')->nullable()->after('inventory_batch_id')->constrained('inventory_bale_rolls')->onDelete('set null');
        });

        Schema::table('job_material_consumptions', function (Blueprint $table) {
            $table->foreignId('inventory_bale_roll_id')->nullable()->after('inventory_batch_id')->constrained('inventory_bale_rolls')->onDelete('set null');
        });

        Schema::table('job_wastages', function (Blueprint $table) {
            $table->foreignId('inventory_bale_roll_id')->nullable()->after('manufacturing_product_id')->constrained('inventory_bale_rolls')->onDelete('set null');
        });

        Schema::table('job_labor_allocations', function (Blueprint $table) {
            $table->foreignId('inventory_bale_roll_id')->nullable()->after('manufacturing_product_id')->constrained('inventory_bale_rolls')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_labor_allocations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_bale_roll_id');
        });

        Schema::table('job_wastages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_bale_roll_id');
        });

        Schema::table('job_material_consumptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_bale_roll_id');
        });

        Schema::table('job_production_outputs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_bale_roll_id');
        });
    }
};
