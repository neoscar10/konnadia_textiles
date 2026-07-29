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
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->date('batch_date');
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('manufacturing_product_id')->constrained('manufacturing_products')->onDelete('cascade');
            $table->integer('planned_quantity');
            $table->string('priority')->default('Normal'); // Urgent, High, Normal, Low
            $table->string('status')->default('Created'); // Created, In Progress, Completed, Closed, Cancelled
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
