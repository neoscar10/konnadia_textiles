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
        Schema::table('production_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('production_batches', 'pattern_id')) {
                $table->foreignId('pattern_id')->nullable()->after('manufacturing_product_id')->constrained('manufacturing_product_patterns')->nullOnDelete();
            }
        });

        Schema::table('production_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_jobs', 'pattern_id')) {
                $table->foreignId('pattern_id')->nullable()->after('manufacturing_product_id')->constrained('manufacturing_product_patterns')->nullOnDelete();
            }
        });

        Schema::table('job_stage_executions', function (Blueprint $table) {
            if (!Schema::hasColumn('job_stage_executions', 'is_skipped')) {
                $table->boolean('is_skipped')->default(false)->after('status');
            }
        });

        Schema::table('job_alterations', function (Blueprint $table) {
            if (Schema::hasColumn('job_alterations', 'child_production_batch_id')) {
                $table->foreignId('child_production_batch_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            if (Schema::hasColumn('production_batches', 'pattern_id')) {
                $table->dropForeign(['pattern_id']);
                $table->dropColumn('pattern_id');
            }
        });

        Schema::table('production_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('production_jobs', 'pattern_id')) {
                $table->dropForeign(['pattern_id']);
                $table->dropColumn('pattern_id');
            }
        });

        Schema::table('job_stage_executions', function (Blueprint $table) {
            if (Schema::hasColumn('job_stage_executions', 'is_skipped')) {
                $table->dropColumn('is_skipped');
            }
        });
    }
};
