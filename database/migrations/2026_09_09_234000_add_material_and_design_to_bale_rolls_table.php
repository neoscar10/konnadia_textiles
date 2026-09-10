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
        Schema::table('inventory_bale_rolls', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_bale_rolls', 'raw_material_id')) {
                $table->foreignId('raw_material_id')->nullable()->after('inventory_bale_id')->constrained('raw_materials')->onDelete('cascade');
            }
            if (!Schema::hasColumn('inventory_bale_rolls', 'fabric_width_id')) {
                $table->foreignId('fabric_width_id')->nullable()->after('raw_material_id')->constrained('fabric_widths')->onDelete('set null');
            }
            if (!Schema::hasColumn('inventory_bale_rolls', 'design_number')) {
                $table->string('design_number')->nullable()->after('roll_number');
            }
            if (!Schema::hasColumn('inventory_bale_rolls', 'stock_id')) {
                $table->string('stock_id')->nullable()->after('design_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_bale_rolls', function (Blueprint $table) {
            $table->dropForeign(['raw_material_id']);
            $table->dropForeign(['fabric_width_id']);
            $table->dropColumn(['raw_material_id', 'fabric_width_id', 'design_number', 'stock_id']);
        });
    }
};
