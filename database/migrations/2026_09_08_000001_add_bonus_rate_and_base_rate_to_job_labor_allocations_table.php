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
        Schema::table('job_labor_allocations', function (Blueprint $table) {
            if (!Schema::hasColumn('job_labor_allocations', 'base_rate')) {
                $table->decimal('base_rate', 10, 2)->nullable()->default(0.00)->after('quantity_processed');
            }
            if (!Schema::hasColumn('job_labor_allocations', 'bonus_rate')) {
                $table->decimal('bonus_rate', 10, 2)->default(0.00)->after('base_rate');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_labor_allocations', function (Blueprint $table) {
            if (Schema::hasColumn('job_labor_allocations', 'base_rate')) {
                $table->dropColumn('base_rate');
            }
            if (Schema::hasColumn('job_labor_allocations', 'bonus_rate')) {
                $table->dropColumn('bonus_rate');
            }
        });
    }
};
