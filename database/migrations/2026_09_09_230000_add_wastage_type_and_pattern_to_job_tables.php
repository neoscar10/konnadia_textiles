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
        if (Schema::hasTable('job_wastages') && !Schema::hasColumn('job_wastages', 'wastage_type')) {
            Schema::table('job_wastages', function (Blueprint $table) {
                $table->string('wastage_type')->default('damage')->after('task_id');
            });
        }

        if (Schema::hasTable('job_alterations')) {
            Schema::table('job_alterations', function (Blueprint $table) {
                if (!Schema::hasColumn('job_alterations', 'target_pattern_id')) {
                    $table->foreignId('target_pattern_id')->nullable()->constrained('manufacturing_product_patterns')->nullOnDelete()->after('target_product_id');
                }
                if (!Schema::hasColumn('job_alterations', 'child_production_job_id')) {
                    $table->foreignId('child_production_job_id')->nullable()->constrained('production_jobs')->nullOnDelete()->after('child_production_batch_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('job_wastages') && Schema::hasColumn('job_wastages', 'wastage_type')) {
            Schema::table('job_wastages', function (Blueprint $table) {
                $table->dropColumn('wastage_type');
            });
        }

        if (Schema::hasTable('job_alterations')) {
            Schema::table('job_alterations', function (Blueprint $table) {
                if (Schema::hasColumn('job_alterations', 'target_pattern_id')) {
                    $table->dropForeign(['target_pattern_id']);
                    $table->dropColumn('target_pattern_id');
                }
                if (Schema::hasColumn('job_alterations', 'child_production_job_id')) {
                    $table->dropForeign(['child_production_job_id']);
                    $table->dropColumn('child_production_job_id');
                }
            });
        }
    }
};
