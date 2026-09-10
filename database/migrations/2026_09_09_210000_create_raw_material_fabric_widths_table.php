<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('raw_material_fabric_widths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raw_material_id')
                ->constrained('raw_materials', 'id', 'fk_rm_fw_rm_id')
                ->cascadeOnDelete();
            $table->foreignId('fabric_width_id')
                ->constrained('fabric_widths', 'id', 'fk_rm_fw_fw_id')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['raw_material_id', 'fabric_width_id'], 'rm_fw_unique');
        });

        // Migrate existing standard_width values to pivot table
        $materials = DB::table('raw_materials')->whereNotNull('standard_width')->get();
        $now = now();

        foreach ($materials as $material) {
            $widthVal = (float) $material->standard_width;
            if ($widthVal <= 0) {
                continue;
            }

            $fabricWidth = DB::table('fabric_widths')->where('value', $widthVal)->first();

            if (!$fabricWidth) {
                $fwId = DB::table('fabric_widths')->insertGetId([
                    'name' => "{$widthVal} Inch",
                    'value' => $widthVal,
                    'unit' => $material->width_unit ?? 'Inch',
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $fwId = $fabricWidth->id;
            }

            DB::table('raw_material_fabric_widths')->insertOrIgnore([
                'raw_material_id' => $material->id,
                'fabric_width_id' => $fwId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raw_material_fabric_widths');
    }
};
