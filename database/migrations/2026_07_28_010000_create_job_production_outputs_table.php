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
        Schema::create('job_production_outputs', function (Blueprint $table) {
            $table->id();
            $table->string('job_code');
            $table->foreignId('production_job_id')->nullable()->constrained('production_jobs')->onDelete('cascade');
            $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->integer('quantity_produced');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_production_outputs');
    }
};
