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
        if (!Schema::hasTable('customized_production_orders')) {
            Schema::create('customized_production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('custom_order_id')->unique();
                $table->string('item_description');
                $table->foreignId('raw_material_id')->nullable()->constrained('raw_materials')->nullOnDelete();
                $table->string('fabric_name')->nullable();
                $table->decimal('width', 10, 2)->default(0);
                $table->decimal('length', 10, 2)->default(0);
                $table->string('length_unit')->default('Inch');
                $table->integer('target_quantity')->default(1);
                $table->string('status')->default('in_progress'); // in_progress, completed, cancelled
                $table->text('notes')->nullable();
                $table->foreignId('production_job_id')->nullable()->constrained('production_jobs')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('job_stage_executions') && !Schema::hasColumn('job_stage_executions', 'is_final_step')) {
            Schema::table('job_stage_executions', function (Blueprint $table) {
                $table->boolean('is_final_step')->default(false)->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customized_production_orders');

        if (Schema::hasTable('job_stage_executions') && Schema::hasColumn('job_stage_executions', 'is_final_step')) {
            Schema::table('job_stage_executions', function (Blueprint $table) {
                $table->dropColumn('is_final_step');
            });
        }
    }
};
