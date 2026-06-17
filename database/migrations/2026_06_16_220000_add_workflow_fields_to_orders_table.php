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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'admin_note')) {
                $table->text('admin_note')->nullable()->after('customer_notes');
            }
            if (!Schema::hasColumn('orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('admin_note');
            }
            if (!Schema::hasColumn('orders', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'stock_deducted_at')) {
                $table->timestamp('stock_deducted_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'credit_applied_at')) {
                $table->timestamp('credit_applied_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'credit_reversed_at')) {
                $table->timestamp('credit_reversed_at')->nullable();
            }
        });

        Schema::table('order_payment_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('order_payment_receipts', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('verified_at');
            }
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('order_status_histories', 'metadata')) {
                $table->json('metadata')->nullable()->after('note');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'admin_note',
                'rejection_reason',
                'approved_at',
                'rejected_at',
                'dispatched_at',
                'stock_deducted_at',
                'credit_applied_at',
                'credit_reversed_at'
            ]);
        });

        Schema::table('order_payment_receipts', function (Blueprint $table) {
            $table->dropColumn('rejected_at');
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
