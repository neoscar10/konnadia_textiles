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
        // Add unit_type to raw_material_categories so each category
        // determines which unit family its materials can use
        Schema::table('raw_material_categories', function (Blueprint $table) {
            $table->string('unit_type')->default('other')->after('code'); // 'length_based' or 'other'
            $table->string('description')->nullable()->after('unit_type');
            $table->boolean('is_active')->default(true)->after('description');
        });

        // Enhance raw_materials with status
        Schema::table('raw_materials', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('raw_material_categories', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'description', 'is_active']);
        });

        Schema::table('raw_materials', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
