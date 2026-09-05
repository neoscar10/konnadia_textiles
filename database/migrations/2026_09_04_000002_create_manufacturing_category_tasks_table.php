<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_category_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_product_category_id')
                ->constrained('manufacturing_product_categories')
                ->cascadeOnDelete();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->integer('sequence_number')->default(1);
            $table->decimal('standard_labor_rate', 10, 2)->nullable();
            $table->boolean('is_final_step')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_category_tasks');
    }
};
