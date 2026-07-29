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
        Schema::table('production_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_jobs', 'production_batch_db_id')) {
                $table->foreignId('production_batch_db_id')->nullable()->after('production_batch_id')->constrained('production_batches')->onDelete('cascade');
            }
            if (!Schema::hasColumn('production_jobs', 'task_id')) {
                $table->foreignId('task_id')->nullable()->after('manufacturing_product_id')->constrained('tasks')->onDelete('cascade');
            }
            if (!Schema::hasColumn('production_jobs', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('task_id')->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('production_jobs', 'job_date')) {
                $table->date('job_date')->nullable()->after('supervisor_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropForeign(['production_batch_db_id']);
            $table->dropForeign(['task_id']);
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['production_batch_db_id', 'task_id', 'supervisor_id', 'job_date']);
        });
    }
};
