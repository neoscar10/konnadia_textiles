<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // banner, category_slider, product_slider, image_slider
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('display_style')->nullable();
            $table->unsignedInteger('items_per_view')->nullable();
            $table->unsignedInteger('display_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('home_content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_content_section_id')
                ->constrained('home_content_sections')
                ->cascadeOnDelete();
            $table->string('item_type')->nullable(); // image, product, category, banner
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('link_type')->default('none'); // none, category, product, url
            $table->foreignId('link_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('link_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_content_items');
        Schema::dropIfExists('home_content_sections');
    }
};
