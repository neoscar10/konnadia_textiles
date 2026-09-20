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
            if (!Schema::hasColumn('production_batches', 'cutter_id')) {
                $table->foreignId('cutter_id')->nullable()->after('factory_supervisor_id')->constrained('labors')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_batches', function (Blueprint $table) {
            if (Schema::hasColumn('production_batches', 'cutter_id')) {
                $table->dropForeign(['cutter_id']);
                $table->dropColumn('cutter_id');
            }
        });
    }
};
