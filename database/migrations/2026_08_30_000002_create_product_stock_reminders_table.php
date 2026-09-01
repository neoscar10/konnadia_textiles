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
        Schema::create('product_stock_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_combination_id')->nullable()->constrained('product_combinations')->onDelete('cascade');
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->onDelete('cascade');
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', ['pending', 'notified', 'cancelled'])->default('pending')->index();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status'], 'psr_prod_status_idx');
            $table->index(['product_id', 'product_combination_id', 'status'], 'psr_prod_comb_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stock_reminders');
    }
};
