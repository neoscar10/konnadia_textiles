<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_batch_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_batch_id')
                ->constrained('inventory_batches')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action'); // e.g., created, consumed, adjusted, deleted
            $table->decimal('quantity', 15, 4)->nullable(); // quantity affected
            $table->foreignId('related_production_batch_id')
                ->nullable()
                ->constrained('production_batches')
                ->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_batch_logs');
    }
};
?>
