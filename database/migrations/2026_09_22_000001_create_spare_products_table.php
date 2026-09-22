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
        Schema::create('spare_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_batch_id')->nullable()->constrained('production_batches')->nullOnDelete();
            $table->foreignId('production_job_id')->nullable()->constrained('production_jobs')->nullOnDelete();
            $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products')->cascadeOnDelete();
            $table->string('design_id', 100)->default('DEFAULT')->index();
            $table->integer('quantity')->default(0);
            $table->integer('used_quantity')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spare_products');
    }
};
