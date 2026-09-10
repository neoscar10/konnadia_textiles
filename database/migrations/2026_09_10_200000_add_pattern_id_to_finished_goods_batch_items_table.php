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
        if (Schema::hasTable('finished_goods_batch_items') && !Schema::hasColumn('finished_goods_batch_items', 'pattern_id')) {
            Schema::table('finished_goods_batch_items', function (Blueprint $table) {
                $table->foreignId('pattern_id')->nullable()->after('manufacturing_product_id')->constrained('manufacturing_product_patterns')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('finished_goods_batch_items') && Schema::hasColumn('finished_goods_batch_items', 'pattern_id')) {
            Schema::table('finished_goods_batch_items', function (Blueprint $table) {
                $table->dropForeign(['pattern_id']);
                $table->dropColumn('pattern_id');
            });
        }
    }
};
