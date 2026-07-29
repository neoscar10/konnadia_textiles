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
            if (!Schema::hasColumn('production_batches', 'parent_batch_id')) {
                $table->foreignId('parent_batch_id')->nullable()->after('id')->constrained('production_batches')->onDelete('cascade');
            }
        });

        Schema::create('job_alterations', function (Blueprint $table) {
            $table->id();
            $table->string('job_code');
            $table->foreignId('production_job_id')->nullable()->constrained('production_jobs')->onDelete('cascade');
            $table->foreignId('source_product_id')->constrained('manufacturing_products')->onDelete('cascade');
            $table->integer('source_quantity');
            $table->foreignId('target_product_id')->constrained('manufacturing_products')->onDelete('cascade');
            $table->integer('target_quantity');
            $table->foreignId('child_production_batch_id')->constrained('production_batches')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_alterations');

        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropForeign(['parent_batch_id']);
            $table->dropColumn('parent_batch_id');
        });
    }
};
