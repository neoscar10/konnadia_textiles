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
        Schema::table('cart_items', function (Blueprint $table) {
            $table->integer('quantity_lvl1')->nullable()->default(0)->after('quantity');
            $table->integer('quantity_lvl2')->nullable()->default(0)->after('quantity_lvl1');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('quantity_lvl1')->nullable()->default(0)->after('quantity');
            $table->integer('quantity_lvl2')->nullable()->default(0)->after('quantity_lvl1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_lvl1', 'quantity_lvl2']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_lvl1', 'quantity_lvl2']);
        });
    }
};
