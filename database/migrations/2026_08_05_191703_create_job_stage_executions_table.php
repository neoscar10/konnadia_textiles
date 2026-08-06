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
        Schema::create('job_stage_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_job_id')->constrained('production_jobs')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->integer('sequence_number')->default(1);
            $table->integer('target_quantity')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Migrate existing ProductionJob data before removing task_id column
        if (Schema::hasColumn('production_jobs', 'task_id')) {
            $jobs = DB::table('production_jobs')->get();
            $batchMasterJobMap = [];

            foreach ($jobs as $job) {
                $batchId = $job->production_batch_db_id ?? $job->production_batch_id;

                if (!isset($batchMasterJobMap[$batchId])) {
                    // First job encountered for this batch becomes master job
                    $batchMasterJobMap[$batchId] = $job->id;

                    if ($job->task_id) {
                        DB::table('job_stage_executions')->insert([
                            'production_job_id' => $job->id,
                            'task_id' => $job->task_id,
                            'sequence_number' => 1,
                            'target_quantity' => $job->target_quantity,
                            'status' => $job->status,
                            'created_at' => $job->created_at,
                            'updated_at' => $job->updated_at,
                        ]);
                    }
                } else {
                    $masterJobId = $batchMasterJobMap[$batchId];
                    $masterJob = DB::table('production_jobs')->find($masterJobId);

                    // Re-link child logs to master job
                    DB::table('job_labor_allocations')
                        ->where('job_id', $job->job_code)
                        ->update(['job_id' => $masterJob->job_code]);

                    DB::table('job_material_consumptions')
                        ->where('production_job_id', $job->id)
                        ->update(['production_job_id' => $masterJobId, 'job_code' => $masterJob->job_code]);

                    DB::table('job_production_outputs')
                        ->where('production_job_id', $job->id)
                        ->update(['production_job_id' => $masterJobId, 'job_code' => $masterJob->job_code]);

                    DB::table('job_wastages')
                        ->where('production_job_id', $job->id)
                        ->update(['production_job_id' => $masterJobId, 'job_code' => $masterJob->job_code]);

                    DB::table('job_alterations')
                        ->where('production_job_id', $job->id)
                        ->update(['production_job_id' => $masterJobId, 'job_code' => $masterJob->job_code]);

                    if ($job->task_id) {
                        $nextSeq = DB::table('job_stage_executions')->where('production_job_id', $masterJobId)->count() + 1;
                        DB::table('job_stage_executions')->insert([
                            'production_job_id' => $masterJobId,
                            'task_id' => $job->task_id,
                            'sequence_number' => $nextSeq,
                            'target_quantity' => $job->target_quantity,
                            'status' => $job->status,
                            'created_at' => $job->created_at,
                            'updated_at' => $job->updated_at,
                        ]);
                    }

                    // Delete secondary job row
                    DB::table('production_jobs')->where('id', $job->id)->delete();
                }
            }

            // Drop task_id from production_jobs
            Schema::table('production_jobs', function (Blueprint $table) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_jobs', 'task_id')) {
                $table->foreignId('task_id')->nullable()->after('manufacturing_product_id')->constrained('tasks')->onDelete('cascade');
            }
        });

        Schema::dropIfExists('job_stage_executions');
    }
};
