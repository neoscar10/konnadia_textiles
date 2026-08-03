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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'consumes_raw_material')) {
                $table->boolean('consumes_raw_material')->default(false)->after('status');
            }
            if (!Schema::hasColumn('tasks', 'is_labor_required')) {
                $table->boolean('is_labor_required')->default(true)->after('consumes_raw_material');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['consumes_raw_material', 'is_labor_required']);
        });
    }
};
