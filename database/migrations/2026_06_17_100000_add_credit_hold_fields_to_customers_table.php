<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'credit_hold')) {
                $table->boolean('credit_hold')->default(false)->after('allow_credit_beyond_limit');
            }
            if (!Schema::hasColumn('customers', 'credit_hold_reason')) {
                $table->text('credit_hold_reason')->nullable()->after('credit_hold');
            }
            if (!Schema::hasColumn('customers', 'credit_hold_at')) {
                $table->timestamp('credit_hold_at')->nullable()->after('credit_hold_reason');
            }
            if (!Schema::hasColumn('customers', 'credit_hold_by')) {
                $table->foreignId('credit_hold_by')->nullable()->after('credit_hold_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('customers', 'last_credit_review_at')) {
                $table->timestamp('last_credit_review_at')->nullable()->after('credit_hold_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'credit_hold_by')) {
                $table->dropForeign(['credit_hold_by']);
                $table->dropColumn('credit_hold_by');
            }
            foreach (['credit_hold', 'credit_hold_reason', 'credit_hold_at', 'last_credit_review_at'] as $col) {
                if (Schema::hasColumn('customers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
