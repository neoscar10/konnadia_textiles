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
        // 1. Add is_manufactured to products if not exists
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'is_manufactured')) {
                $table->boolean('is_manufactured')->default(false)->after('is_active');
            }
        });

        // 2. Create retail_shops table
        Schema::create('retail_shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Create product_transfers table
        Schema::create('product_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number')->unique();
            $table->foreignId('retail_shop_id')->constrained('retail_shops')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('draft'); // draft, completed, cancelled
            $table->date('transfer_date')->nullable();
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_quantity_base_units', 15, 4)->default(0);
            $table->boolean('stock_deducted')->default(false);
            $table->timestamp('stock_deducted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Create product_transfer_items table
        Schema::create('product_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_transfer_id')->constrained('product_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('product_combination_id')->nullable()->constrained('product_combinations')->nullOnDelete();
            $table->foreignId('product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('product_title');
            $table->string('product_sku')->nullable();
            $table->json('selected_options')->nullable();
            $table->string('unit_name')->nullable();
            $table->string('unit_short_code')->nullable();
            $table->decimal('unit_conversion_quantity', 12, 4)->default(1);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('base_quantity', 15, 4)->default(0);
            $table->decimal('available_stock_before', 15, 4)->nullable();
            $table->decimal('available_stock_after', 15, 4)->nullable();
            $table->boolean('stock_tracked')->default(false);
            $table->boolean('stock_deducted')->default(false);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_transfer_items');
        Schema::dropIfExists('product_transfers');
        Schema::dropIfExists('retail_shops');
        
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'is_manufactured')) {
                $table->dropColumn('is_manufactured');
            }
        });
    }
};
