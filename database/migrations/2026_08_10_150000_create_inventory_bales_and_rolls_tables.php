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
        // 1. Add num_bales and declared_bale_length to inventory_batches
        if (!Schema::hasColumn('inventory_batches', 'num_bales')) {
            Schema::table('inventory_batches', function (Blueprint $table) {
                $table->integer('num_bales')->nullable()->after('unit');
                $table->decimal('declared_bale_length', 12, 4)->nullable()->after('num_bales');
            });
        }

        // 2. Create inventory_bales table
        if (!Schema::hasTable('inventory_bales')) {
            Schema::create('inventory_bales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_batch_id')->constrained('inventory_batches')->onDelete('cascade');
                $table->string('bale_number');
                $table->string('status')->default('unopened'); // unopened, opened, depleted
                $table->decimal('declared_length', 12, 4)->nullable();
                $table->decimal('actual_recorded_length', 12, 4)->nullable();
                $table->decimal('current_balance_length', 12, 4)->default(0.0000);
                $table->integer('roll_count')->nullable();
                $table->timestamps();
            });
        }

        // 3. Create inventory_bale_rolls table
        if (!Schema::hasTable('inventory_bale_rolls')) {
            Schema::create('inventory_bale_rolls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_bale_id')->constrained('inventory_bales')->onDelete('cascade');
                $table->string('roll_number');
                $table->decimal('initial_length', 12, 4);
                $table->decimal('current_balance_length', 12, 4);
                $table->string('status')->default('active'); // active, depleted
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_bale_rolls');
        Schema::dropIfExists('inventory_bales');

        if (Schema::hasTable('inventory_batches') && Schema::hasColumn('inventory_batches', 'num_bales')) {
            Schema::table('inventory_batches', function (Blueprint $table) {
                $table->dropColumn(['num_bales', 'declared_bale_length']);
            });
        }
    }
};
