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
        Schema::create('job_labor_allocations', function (Blueprint $table) {
            $table->id();
            $table->string('production_batch_id')->nullable();
            $table->string('job_id')->nullable();
            $table->foreignId('labor_id')->constrained('labors')->onDelete('cascade');
            $table->foreignId('manufacturing_product_id')->nullable()->constrained('manufacturing_products')->onDelete('set null');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->integer('quantity_processed');
            $table->decimal('calculated_wage', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_labor_allocations');
    }
};
