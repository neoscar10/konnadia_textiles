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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('sku')->unique();
            $table->string('product_code')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('base_price', 15, 2)->default(0);
            $table->longText('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('stock_quantity')->default(0); // overall stock for non-variant products
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'category_id']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type')->default('image');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->string('alt_text')->nullable();
            $table->timestamps();
        });

        Schema::create('product_variation_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('display_type')->default('text'); // text, color, image
            $table->boolean('has_images')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_group_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('color_hex')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variation_value_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_value_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_combinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->nullable();
            $table->json('combination_values'); 
            $table->decimal('price', 15, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_customer_level_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_level_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'customer_level_id'], 'prod_cust_level_unique');
        });

        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->string('short_code');
            $table->decimal('conversion_to_base', 12, 4)->default(1);
            $table->timestamps();

            $table->unique(['product_id', 'level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('product_customer_level_prices');
        Schema::dropIfExists('product_combinations');
        Schema::dropIfExists('product_variation_value_media');
        Schema::dropIfExists('product_variation_values');
        Schema::dropIfExists('product_variation_groups');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_category');
        Schema::dropIfExists('products');
    }
};
