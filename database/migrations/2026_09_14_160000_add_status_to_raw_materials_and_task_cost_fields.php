<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('raw_materials') && !Schema::hasColumn('raw_materials', 'status')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->string('status')->default('active')->after('is_active');
            });

            DB::table('raw_materials')->update([
                'status' => DB::raw("CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END")
            ]);
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'cost_type')) {
                    $table->string('cost_type')->default('piece_rate')->after('is_labor_required');
                }
                if (!Schema::hasColumn('tasks', 'default_piece_rate')) {
                    $table->decimal('default_piece_rate', 10, 2)->default(0.00)->after('cost_type');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('raw_materials') && Schema::hasColumn('raw_materials', 'status')) {
            Schema::table('raw_materials', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'cost_type')) {
                    $table->dropColumn('cost_type');
                }
                if (Schema::hasColumn('tasks', 'default_piece_rate')) {
                    $table->dropColumn('default_piece_rate');
                }
            });
        }
    }
};
