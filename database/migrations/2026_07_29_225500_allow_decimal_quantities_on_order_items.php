<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('quantity', 12, 4)->change();
            $table->decimal('quantity_lvl1', 12, 4)->nullable()->change();
            $table->decimal('quantity_lvl2', 12, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->change();
            $table->integer('quantity_lvl1')->nullable()->change();
            $table->integer('quantity_lvl2')->nullable()->change();
        });
    }
};
