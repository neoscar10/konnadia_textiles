<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('manufacturing_products', 'is_common_subsidiary')) {
            Schema::table('manufacturing_products', function (Blueprint $table) {
                $table->boolean('is_common_subsidiary')->default(true);
            });
        }

        if (!Schema::hasColumn('manufacturing_product_patterns', 'is_subsidiary_used')) {
            Schema::table('manufacturing_product_patterns', function (Blueprint $table) {
                $table->boolean('is_subsidiary_used')->default(false);
            });
        }

        if (!Schema::hasTable('manufacturing_pattern_fabric_widths')) {
            Schema::create('manufacturing_pattern_fabric_widths', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pattern_id')
                    ->constrained('manufacturing_product_patterns', 'id', 'mfg_pat_fw_pat_fk')
                    ->cascadeOnDelete();
                $table->foreignId('fabric_width_id')
                    ->nullable()
                    ->constrained('fabric_widths', 'id', 'mfg_pat_fw_fw_fk')
                    ->nullOnDelete();
                $table->decimal('fabric_length', 10, 4)->default(0.0000);
                $table->string('fabric_length_unit')->default('m');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('manufacturing_pattern_subsidiary_materials')) {
            Schema::create('manufacturing_pattern_subsidiary_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pattern_id')
                    ->constrained('manufacturing_product_patterns', 'id', 'mfg_pat_sub_pat_fk')
                    ->cascadeOnDelete();
                $table->foreignId('raw_material_id')
                    ->constrained('raw_materials', 'id', 'mfg_pat_sub_raw_fk')
                    ->cascadeOnDelete();
                $table->decimal('consumption_quantity', 10, 4)->default(1.0000);
                $table->timestamps();
            });
        }

        // Migrate existing pattern fabric width & length data into manufacturing_pattern_fabric_widths
        $patterns = DB::table('manufacturing_product_patterns')->get();
        $now = now();

        foreach ($patterns as $pattern) {
            if ($pattern->fabric_width_id && $pattern->fabric_length) {
                $exists = DB::table('manufacturing_pattern_fabric_widths')
                    ->where('pattern_id', $pattern->id)
                    ->where('fabric_width_id', $pattern->fabric_width_id)
                    ->exists();

                if (!$exists) {
                    DB::table('manufacturing_pattern_fabric_widths')->insert([
                        'pattern_id' => $pattern->id,
                        'fabric_width_id' => $pattern->fabric_width_id,
                        'fabric_length' => $pattern->fabric_length,
                        'fabric_length_unit' => $pattern->fabric_length_unit ?? 'm',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Sync product subsidiary materials to pattern if product is_subsidiary_used is true
            $product = DB::table('manufacturing_products')->where('id', $pattern->manufacturing_product_id)->first();
            if ($product && !empty($product->is_subsidiary_used)) {
                DB::table('manufacturing_product_patterns')
                    ->where('id', $pattern->id)
                    ->update(['is_subsidiary_used' => true]);

                $productSubs = DB::table('manufacturing_product_subsidiary_materials')
                    ->where('manufacturing_product_id', $product->id)
                    ->get();

                foreach ($productSubs as $ps) {
                    $subExists = DB::table('manufacturing_pattern_subsidiary_materials')
                        ->where('pattern_id', $pattern->id)
                        ->where('raw_material_id', $ps->raw_material_id)
                        ->exists();

                    if (!$subExists) {
                        DB::table('manufacturing_pattern_subsidiary_materials')->insert([
                            'pattern_id' => $pattern->id,
                            'raw_material_id' => $ps->raw_material_id,
                            'consumption_quantity' => $ps->consumption_quantity,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturing_pattern_subsidiary_materials');
        Schema::dropIfExists('manufacturing_pattern_fabric_widths');

        if (Schema::hasColumn('manufacturing_product_patterns', 'is_subsidiary_used')) {
            Schema::table('manufacturing_product_patterns', function (Blueprint $table) {
                $table->dropColumn('is_subsidiary_used');
            });
        }

        if (Schema::hasColumn('manufacturing_products', 'is_common_subsidiary')) {
            Schema::table('manufacturing_products', function (Blueprint $table) {
                $table->dropColumn('is_common_subsidiary');
            });
        }
    }
};
