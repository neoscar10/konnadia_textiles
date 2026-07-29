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
        Schema::create('raw_material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_category_id')->constrained('raw_material_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('unit')->default('Meters'); // Meters, Rolls, Pieces, Kgs
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')->constrained('raw_materials')->onDelete('cascade');
            $table->string('batch_number')->unique();
            $table->decimal('received_quantity', 12, 2);
            $table->decimal('balance_quantity', 12, 2);
            $table->string('unit')->default('Meters');
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('task_raw_material_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('raw_material_category_id')->constrained('raw_material_categories')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('job_material_consumptions', function (Blueprint $table) {
            $table->id();
            $table->string('job_code');
            $table->foreignId('production_job_id')->nullable()->constrained('production_jobs')->onDelete('cascade');
            $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->onDelete('cascade');
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->decimal('quantity_consumed', 12, 2);
            $table->decimal('unit_cost', 10, 2)->default(0.00);
            $table->decimal('total_cost', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_material_consumptions');
        Schema::dropIfExists('task_raw_material_category');
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('raw_materials');
        Schema::dropIfExists('raw_material_categories');
    }
};
