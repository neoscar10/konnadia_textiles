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
        Schema::table('manufacturing_products', function (Blueprint $table) {
            if (!Schema::hasColumn('manufacturing_products', 'status')) {
                $table->string('status')->default('active')->after('code');
                $table->index(['code', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_products', function (Blueprint $table) {
            $table->dropIndex(['code', 'status']);
            $table->dropColumn('status');
        });
    }
};
