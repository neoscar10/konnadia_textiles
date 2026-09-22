<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Revert any production jobs marked as 'completed' whose stage executions are not all completed
        $completedJobIds = DB::table('production_jobs')
            ->where('status', 'completed')
            ->pluck('id');

        foreach ($completedJobIds as $jobId) {
            $hasIncomplete = DB::table('job_stage_executions')
                ->where('production_job_id', $jobId)
                ->where('status', '!=', 'completed')
                ->exists();

            if ($hasIncomplete) {
                DB::table('production_jobs')
                    ->where('id', $jobId)
                    ->update(['status' => 'in_progress']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed as this is a data correction migration
    }
};
