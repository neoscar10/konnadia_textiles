<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:jobs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all job batches and job entries from the database (jobs, failed_jobs, job_batches).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning job tables...');
        Schema::disableForeignKeyConstraints();

        $tables = [
            'jobs',
            'failed_jobs',
            'job_batches',
            'production_batches',
            'production_jobs',
            'job_stage_executions',
            'job_labor_allocations',
            'job_material_consumptions',
            'job_production_outputs',
            'job_wastages',
            'job_alterations'
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->info(" → {$table} table cleared");
            }
        }

        Schema::enableForeignKeyConstraints();
        $this->info('All job and production batch related tables have been cleaned.');
        return 0;
    }
}
