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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number')->unique();
            $table->foreignId('customer_level_id')
                ->constrained('customer_levels')
                ->restrictOnDelete();
            
            $table->string('company_name');
            $table->string('gst_number', 30);
            $table->string('contact_person');
            $table->string('mobile_number', 30);
            $table->string('email')->nullable();

            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('outstanding_amount', 15, 2)->default(0);
            $table->decimal('available_credit', 15, 2)->default(0);
            $table->decimal('overdue_amount', 15, 2)->default(0);

            $table->boolean('allow_credit_beyond_limit')->default(false);

            $table->text('billing_address')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
