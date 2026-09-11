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
        if (Schema::hasTable('monthly_overhead_material_items')) {
            Schema::table('monthly_overhead_material_items', function (Blueprint $table) {
                if (!Schema::hasColumn('monthly_overhead_material_items', 'opening_stock_qty')) {
                    $table->decimal('opening_stock_qty', 10, 2)->default(0.00)->after('raw_material_id');
                }
                if (!Schema::hasColumn('monthly_overhead_material_items', 'purchases_qty')) {
                    $table->decimal('purchases_qty', 10, 2)->default(0.00)->after('opening_stock_value');
                }
                if (!Schema::hasColumn('monthly_overhead_material_items', 'unit_cost')) {
                    $table->decimal('unit_cost', 12, 2)->default(0.00)->after('purchases_value');
                }
                if (!Schema::hasColumn('monthly_overhead_material_items', 'consumed_qty')) {
                    $table->decimal('consumed_qty', 10, 2)->default(0.00)->after('closing_stock_value');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('monthly_overhead_material_items')) {
            Schema::table('monthly_overhead_material_items', function (Blueprint $table) {
                $table->dropColumn(['opening_stock_qty', 'purchases_qty', 'unit_cost', 'consumed_qty']);
            });
        }
    }
};
