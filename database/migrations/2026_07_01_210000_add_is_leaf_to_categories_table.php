<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // is_leaf marks the final layer of the hierarchy — these categories
            // hold products directly instead of sub-categories.
            $table->boolean('is_leaf')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_leaf');
        });
    }
};
