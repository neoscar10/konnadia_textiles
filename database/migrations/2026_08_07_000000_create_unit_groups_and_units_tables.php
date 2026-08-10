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
        // 1. Unit Groups table
        if (!Schema::hasTable('unit_groups')) {
            Schema::create('unit_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Units table
        if (!Schema::hasTable('units')) {
            Schema::create('units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unit_group_id')->constrained('unit_groups')->onDelete('cascade');
                $table->string('name');
                $table->string('short_code');
                $table->boolean('is_base')->default(false);
                $table->decimal('ratio_to_base', 16, 6)->default(1.000000);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Add unit_group_id to raw_material_categories
        if (!Schema::hasColumn('raw_material_categories', 'unit_group_id')) {
            Schema::table('raw_material_categories', function (Blueprint $table) {
                $table->foreignId('unit_group_id')->nullable()->after('code')->constrained('unit_groups')->nullOnDelete();
            });
        }

        // 4. Add unit_group_id and unit_id to raw_materials
        if (!Schema::hasColumn('raw_materials', 'unit_group_id')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->foreignId('unit_group_id')->nullable()->after('raw_material_category_id')->constrained('unit_groups')->nullOnDelete();
                $table->foreignId('unit_id')->nullable()->after('unit_group_id')->constrained('units')->nullOnDelete();
            });
        }

        // 5. Add purchase_unit_id and base_quantity to inventory_batches
        if (!Schema::hasColumn('inventory_batches', 'purchase_unit_id')) {
            Schema::table('inventory_batches', function (Blueprint $table) {
                $table->foreignId('purchase_unit_id')->nullable()->after('unit')->constrained('units')->nullOnDelete();
                $table->decimal('base_quantity', 16, 4)->nullable()->after('received_quantity');
                $table->decimal('base_current_balance', 16, 4)->nullable()->after('balance_quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inventory_batches') && Schema::hasColumn('inventory_batches', 'purchase_unit_id')) {
            Schema::table('inventory_batches', function (Blueprint $table) {
                $table->dropForeign(['purchase_unit_id']);
                $table->dropColumn(['purchase_unit_id', 'base_quantity', 'base_current_balance']);
            });
        }

        if (Schema::hasTable('raw_materials') && Schema::hasColumn('raw_materials', 'unit_group_id')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->dropForeign(['unit_group_id']);
                $table->dropForeign(['unit_id']);
                $table->dropColumn(['unit_group_id', 'unit_id']);
            });
        }

        if (Schema::hasTable('raw_material_categories') && Schema::hasColumn('raw_material_categories', 'unit_group_id')) {
            Schema::table('raw_material_categories', function (Blueprint $table) {
                $table->dropForeign(['unit_group_id']);
                $table->dropColumn(['unit_group_id']);
            });
        }

        Schema::dropIfExists('units');
        Schema::dropIfExists('unit_groups');
    }
};
