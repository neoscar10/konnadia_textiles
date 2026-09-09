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
        Schema::create('monthly_overhead_allocations', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->date('period_date')->unique();
            $table->decimal('production_value', 12, 2)->default(0.00);
            $table->decimal('stitching_material_total', 12, 2)->default(0.00);
            $table->decimal('salaried_staff_total', 12, 2)->default(0.00);
            $table->decimal('other_overheads_total', 12, 2)->default(0.00);
            $table->decimal('total_overhead', 12, 2)->default(0.00);
            $table->decimal('overhead_percentage', 5, 2)->default(0.00);
            $table->string('status')->default('saved');
            $table->foreignId('created_by')->nullable()->constrained('users', indexName: 'fk_mo_alloc_created_by')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('monthly_overhead_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_overhead_allocation_id')
                ->constrained('monthly_overhead_allocations', indexName: 'fk_mo_mat_items_alloc_id')
                ->onDelete('cascade');
            $table->foreignId('raw_material_id')
                ->constrained('raw_materials', indexName: 'fk_mo_mat_items_raw_mat_id')
                ->onDelete('cascade');
            $table->decimal('opening_stock_value', 12, 2)->default(0.00);
            $table->decimal('purchases_value', 12, 2)->default(0.00);
            $table->decimal('closing_stock_qty', 10, 2)->default(0.00);
            $table->decimal('closing_stock_value', 12, 2)->default(0.00);
            $table->decimal('consumed_cost', 12, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('monthly_overhead_other_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_overhead_allocation_id')
                ->constrained('monthly_overhead_allocations', indexName: 'fk_mo_oth_items_alloc_id')
                ->onDelete('cascade');
            $table->string('category_name');
            $table->decimal('amount', 12, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_overhead_other_items');
        Schema::dropIfExists('monthly_overhead_material_items');
        Schema::dropIfExists('monthly_overhead_allocations');
    }
};
