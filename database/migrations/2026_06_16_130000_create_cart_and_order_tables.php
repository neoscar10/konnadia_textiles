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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('status')->default('active'); // active, converted, abandoned, saved
            $table->string('name')->nullable(); // for future saved cart naming
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_combination_id')->nullable()->constrained('product_combinations')->nullOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('unit_conversion_quantity', 12, 4)->default(1);
            $table->decimal('base_unit_price', 15, 2)->default(0);
            $table->decimal('customer_unit_price', 15, 2)->default(0);
            $table->decimal('line_subtotal', 15, 2)->default(0);
            $table->decimal('gst_percentage', 5, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->json('selected_options')->nullable();

            $table->timestamps();

            $table->unique(
                ['cart_id', 'product_id', 'product_combination_id', 'product_unit_id'],
                'cart_unique_product_option_unit'
            );
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();

            $table->string('status')->default('submitted');
            // submitted, under_review, pending_payment_verification, pending_credit_review, approved, rejected, dispatched, cancelled

            $table->string('checkout_method');
            // manual_payment, credit

            $table->string('payment_status')->default('not_required');
            // not_required, receipt_uploaded, pending_verification, verified, rejected

            $table->string('credit_status')->nullable();
            // within_limit, over_limit_allowed, over_limit_blocked, pending_review

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->decimal('credit_limit_at_order', 15, 2)->nullable();
            $table->decimal('available_credit_at_order', 15, 2)->nullable();
            $table->boolean('used_credit_override_privilege')->default(false);

            $table->text('customer_notes')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_combination_id')->nullable()->constrained('product_combinations')->nullOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            $table->string('product_title');
            $table->string('product_sku')->nullable();
            $table->json('selected_options')->nullable();

            $table->string('unit_name')->nullable();
            $table->string('unit_short_code')->nullable();
            $table->decimal('unit_conversion_quantity', 12, 4)->default(1);

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('base_unit_price', 15, 2)->default(0);
            $table->decimal('customer_unit_price', 15, 2)->default(0);
            $table->decimal('line_subtotal', 15, 2)->default(0);
            $table->decimal('gst_percentage', 5, 2)->default(0);
            $table->decimal('gst_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);

            $table->timestamps();
        });

        Schema::create('order_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->string('status')->default('pending_verification');
            // pending_verification, verified, rejected

            $table->text('admin_note')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_payment_receipts');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
