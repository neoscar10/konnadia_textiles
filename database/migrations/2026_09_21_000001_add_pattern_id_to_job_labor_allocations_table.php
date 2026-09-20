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
        if (Schema::hasTable('job_labor_allocations') && !Schema::hasColumn('job_labor_allocations', 'pattern_id')) {
            Schema::table('job_labor_allocations', function (Blueprint $table) {
                $table->foreignId('pattern_id')->nullable()->after('manufacturing_product_id')->constrained('manufacturing_product_patterns')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_labor_allocations') && Schema::hasColumn('job_labor_allocations', 'pattern_id')) {
            Schema::table('job_labor_allocations', function (Blueprint $table) {
                $table->dropForeign(['pattern_id']);
                $table->dropColumn('pattern_id');
            });
        }
    }
};
