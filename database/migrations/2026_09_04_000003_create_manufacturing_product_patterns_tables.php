<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturing_product_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturing_product_id')
                ->constrained('manufacturing_products')
                ->cascadeOnDelete();
            $table->string('name')->default('Standard');
            $table->foreignId('fabric_width_id')
                ->nullable()
                ->constrained('fabric_widths')
                ->nullOnDelete();
            $table->decimal('fabric_length', 10, 4)->nullable();
            $table->string('fabric_length_unit')->nullable()->default('m');
            $table->decimal('standard_labor_rate', 10, 2)->default(0.00);
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        Schema::create('manufacturing_pattern_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')
                ->constrained('manufacturing_product_patterns')
                ->cascadeOnDelete();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->cascadeOnDelete();
            $table->integer('sequence_number')->default(1);
            $table->decimal('standard_labor_rate', 10, 2)->nullable();
            $table->boolean('is_final_step')->default(false);
            $table->timestamps();
        });

        // Migrate existing manufacturing products to create default "Standard" pattern for each product
        $products = DB::table('manufacturing_products')->get();
        foreach ($products as $p) {
            // Find closest matching fabric width ID if standard_fabric_width is set
            $widthId = null;
            if (!empty($p->standard_fabric_width)) {
                $matchedWidth = DB::table('fabric_widths')
                    ->where('value', $p->standard_fabric_width)
                    ->first();
                $widthId = $matchedWidth?->id;
            }

            $patternId = DB::table('manufacturing_product_patterns')->insertGetId([
                'manufacturing_product_id' => $p->id,
                'name'                     => 'Standard',
                'fabric_width_id'          => $widthId,
                'fabric_length'            => $p->standard_fabric_length,
                'fabric_length_unit'       => $p->fabric_length_unit ?? 'm',
                'standard_labor_rate'      => $p->standard_labor_rate ?? 0.00,
                'is_default'               => true,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            // Copy product's current task routing sequence into the pattern
            $existingTasks = DB::table('manufacturing_product_task')
                ->where('manufacturing_product_id', $p->id)
                ->orderBy('sequence_number')
                ->get();

            foreach ($existingTasks as $t) {
                DB::table('manufacturing_pattern_tasks')->insert([
                    'pattern_id'           => $patternId,
                    'task_id'              => $t->task_id,
                    'sequence_number'      => $t->sequence_number ?? 1,
                    'standard_labor_rate'  => $t->standard_labor_rate ?? $p->standard_labor_rate,
                    'is_final_step'        => $t->is_final_step ?? false,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_pattern_tasks');
        Schema::dropIfExists('manufacturing_product_patterns');
    }
};
