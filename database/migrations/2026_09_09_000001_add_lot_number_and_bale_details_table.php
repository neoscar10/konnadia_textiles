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
        // 1. Add lot_number to inventory_batches
        Schema::table('inventory_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_batches', 'lot_number')) {
                $table->string('lot_number')->nullable()->after('invoice_number');
            }
        });

        // 2. Add per-bale metadata columns to inventory_bales
        Schema::table('inventory_bales', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_bales', 'item_name')) {
                $table->string('item_name')->nullable()->after('bale_number');
            }
            if (!Schema::hasColumn('inventory_bales', 'design_number')) {
                $table->string('design_number')->nullable()->after('item_name');
            }
            if (!Schema::hasColumn('inventory_bales', 'stock_id')) {
                $table->string('stock_id')->nullable()->after('design_number');
            }
            if (!Schema::hasColumn('inventory_bales', 'cost_per_unit')) {
                $table->decimal('cost_per_unit', 10, 2)->nullable()->after('declared_length');
            }
            if (!Schema::hasColumn('inventory_bales', 'total_cost')) {
                $table->decimal('total_cost', 12, 2)->nullable()->after('cost_per_unit');
            }
            if (!Schema::hasColumn('inventory_bales', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('total_cost');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_bales', function (Blueprint $table) {
            $table->dropColumn([
                'item_name',
                'design_number',
                'stock_id',
                'cost_per_unit',
                'total_cost',
                'photo_path',
            ]);
        });

        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropColumn(['lot_number']);
        });
    }
};
