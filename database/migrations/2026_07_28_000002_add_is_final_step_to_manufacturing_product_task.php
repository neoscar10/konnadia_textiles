<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturing_product_task', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_product_task', 'is_final_step')) {
                $table->boolean('is_final_step')->default(false)->after('standard_labor_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_product_task', function (Blueprint $table) {
            $table->dropColumn('is_final_step');
        });
    }
};
