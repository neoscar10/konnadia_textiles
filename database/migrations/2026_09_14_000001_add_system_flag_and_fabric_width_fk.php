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
        // 1. Add is_system to unit_groups table
        if (Schema::hasTable('unit_groups') && !Schema::hasColumn('unit_groups', 'is_system')) {
            Schema::table('unit_groups', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });
        }

        // 2. Add is_system to units table
        if (Schema::hasTable('units') && !Schema::hasColumn('units', 'is_system')) {
            Schema::table('units', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });
        }

        // 3. Add unit_id to fabric_widths table
        if (Schema::hasTable('fabric_widths') && !Schema::hasColumn('fabric_widths', 'unit_id')) {
            Schema::table('fabric_widths', function (Blueprint $table) {
                $table->foreignId('unit_id')->nullable()->after('value')->constrained('units')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fabric_widths') && Schema::hasColumn('fabric_widths', 'unit_id')) {
            Schema::table('fabric_widths', function (Blueprint $table) {
                $table->dropForeign(['unit_id']);
                $table->dropColumn('unit_id');
            });
        }

        if (Schema::hasTable('units') && Schema::hasColumn('units', 'is_system')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }

        if (Schema::hasTable('unit_groups') && Schema::hasColumn('unit_groups', 'is_system')) {
            Schema::table('unit_groups', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
