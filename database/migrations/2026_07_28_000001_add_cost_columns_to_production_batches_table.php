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
        Schema::table('production_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('production_batches', 'total_material_cost')) {
                $table->decimal('total_material_cost', 12, 2)->default(0.00)->after('remarks');
                $table->decimal('total_labor_cost', 12, 2)->default(0.00)->after('total_material_cost');
                $table->decimal('total_wastage_cost', 12, 2)->default(0.00)->after('total_labor_cost');
                $table->decimal('total_manufacturing_cost', 12, 2)->default(0.00)->after('total_wastage_cost');
                $table->decimal('average_unit_cost', 12, 2)->default(0.00)->after('total_manufacturing_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn([
                'total_material_cost',
                'total_labor_cost',
                'total_wastage_cost',
                'total_manufacturing_cost',
                'average_unit_cost',
            ]);
        });
    }
};
