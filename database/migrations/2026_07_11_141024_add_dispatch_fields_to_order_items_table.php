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
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('dispatch_number')->nullable()->after('dispatch_note');
            $table->timestamp('dispatched_at')->nullable()->after('dispatch_number');
            $table->foreignId('dispatched_by_id')->nullable()->constrained('users')->nullOnDelete()->after('dispatched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['dispatched_by_id']);
            $table->dropColumn(['dispatch_number', 'dispatched_at', 'dispatched_by_id']);
        });
    }
};
