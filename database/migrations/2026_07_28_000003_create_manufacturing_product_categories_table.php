<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Create manufacturing product categories table
        Schema::create('manufacturing_product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('status')->default(true)->comment('true = Active, false = Inactive');
            $table->timestamps();
        });

        // Step 2: Add optional FK to manufacturing_products so products can link to a category
        Schema::table('manufacturing_products', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_products', 'manufacturing_product_category_id')) {
                $table->foreignId('manufacturing_product_category_id')
                    ->nullable()
                    ->after('code')
                    ->constrained('manufacturing_product_categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufacturing_product_category_id');
        });

        Schema::dropIfExists('manufacturing_product_categories');
    }
};
