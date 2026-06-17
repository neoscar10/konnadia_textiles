<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_ledgers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();

            $table->string('type');
            // Types: order_credit, payment_received, adjustment_increase, adjustment_decrease,
            //        credit_limit_change, credit_hold, credit_release, credit_privilege_changed, reversal

            $table->string('direction')->nullable();
            // Directions: debit, credit, neutral

            $table->decimal('amount', 15, 2)->default(0);

            $table->decimal('credit_limit_before', 15, 2)->nullable();
            $table->decimal('credit_limit_after', 15, 2)->nullable();

            $table->decimal('outstanding_before', 15, 2)->nullable();
            $table->decimal('outstanding_after', 15, 2)->nullable();

            $table->decimal('available_before', 15, 2)->nullable();
            $table->decimal('available_after', 15, 2)->nullable();

            $table->text('note')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_ledgers');
    }
};
