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
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->string('supplier_name')->nullable()->after('raw_material_id');
            $table->date('purchase_date')->nullable()->after('supplier_name');
            $table->string('invoice_number')->nullable()->after('purchase_date');
            $table->decimal('quantity_received', 10, 4)->nullable()->after('invoice_number');
            $table->decimal('quantity_consumed', 10, 4)->default(0.0000)->after('quantity_received');
            $table->decimal('purchase_rate', 10, 2)->nullable()->after('quantity_consumed');
            $table->decimal('total_amount', 10, 2)->nullable()->after('purchase_rate');
            $table->string('status')->default('active')->after('total_amount'); // active, depleted, expired
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_batches', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_name',
                'purchase_date',
                'invoice_number',
                'quantity_received',
                'quantity_consumed',
                'purchase_rate',
                'total_amount',
                'status',
            ]);
        });
    }
};
