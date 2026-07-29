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
        Schema::table('manufacturing_product_task', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_product_task', 'sequence_number')) {
                $table->integer('sequence_number')->default(1)->after('task_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_product_task', function (Blueprint $table) {
            $table->dropColumn('sequence_number');
        });
    }
};
