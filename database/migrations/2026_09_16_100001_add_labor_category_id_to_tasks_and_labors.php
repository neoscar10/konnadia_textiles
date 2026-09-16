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
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('labor_category_id')
                ->nullable()
                ->after('status')
                ->constrained('labor_categories')
                ->nullOnDelete();
        });

        Schema::table('labors', function (Blueprint $table) {
            $table->foreignId('labor_category_id')
                ->nullable()
                ->after('code')
                ->constrained('labor_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('labors', function (Blueprint $table) {
            $table->dropForeign(['labor_category_id']);
            $table->dropColumn('labor_category_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['labor_category_id']);
            $table->dropColumn('labor_category_id');
        });
    }
};
