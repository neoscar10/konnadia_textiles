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
            $table->string('production_batch_id')->nullable()->change();
            $table->string('job_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_labor_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('production_batch_id')->nullable()->change();
            $table->unsignedBigInteger('job_id')->nullable()->change();
        });
    }
};
