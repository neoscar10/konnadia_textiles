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
            if (!Schema::hasColumn('production_batches', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('production_batches', 'is_converted')) {
                $table->boolean('is_converted')->default(false)->after('completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            $table->dropColumn(['completed_at', 'is_converted']);
        });
    }
};
