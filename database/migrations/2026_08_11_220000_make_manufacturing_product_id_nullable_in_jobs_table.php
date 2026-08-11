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
        Schema::table('production_batches', function (Blueprint $table) {
            $table->foreignId('manufacturing_product_id')->nullable()->change();
        });

        Schema::table('production_jobs', function (Blueprint $table) {
            $table->foreignId('manufacturing_product_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->foreignId('manufacturing_product_id')->nullable(false)->change();
        });

        Schema::table('production_jobs', function (Blueprint $table) {
            $table->foreignId('manufacturing_product_id')->nullable(false)->change();
        });
    }
};
