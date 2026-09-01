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
        Schema::table('product_stock_reminders', function (Blueprint $table) {
            $table->decimal('quantity', 12, 2)->default(1.00)->after('product_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_stock_reminders', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
