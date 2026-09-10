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
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('factory_supervisor_id')->nullable()->after('supervisor_id');
            $table->foreign('factory_supervisor_id')->references('id')->on('factory_supervisors')->nullOnDelete();
        });

        // Backfill existing production_jobs factory_supervisor_id from parent production_batches or supervisor_id
        $batches = DB::table('production_batches')->get();
        foreach ($batches as $batch) {
            if (!empty($batch->factory_supervisor_id)) {
                DB::table('production_jobs')
                    ->where('production_batch_db_id', $batch->id)
                    ->orWhere('production_batch_id', $batch->batch_code)
                    ->update(['factory_supervisor_id' => $batch->factory_supervisor_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropForeign(['factory_supervisor_id']);
            $table->dropColumn('factory_supervisor_id');
        });
    }
};
