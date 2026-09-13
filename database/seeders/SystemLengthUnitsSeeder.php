<?php

namespace Database\Seeders;

use App\Models\UnitGroup;
use App\Models\Unit;
use App\Models\FabricWidth;
use Illuminate\Database\Seeder;

class SystemLengthUnitsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Length Unit Group is System Protected
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        if (!$lengthGroup) {
            $lengthGroup = UnitGroup::create([
                'name' => 'Length based units',
                'code' => 'LENGTH',
                'description' => 'Units for measuring fabric length and linear dimensions',
                'is_active' => true,
                'is_system' => true,
            ]);
        } else {
            $lengthGroup->update([
                'name' => 'Length based units',
                'is_system' => true,
            ]);
        }

        // 2. Core System Length Units
        $systemUnitsData = [
            [
                'name' => 'Meters',
                'short_code' => 'M',
                'is_base' => true,
                'ratio_to_base' => 1.0,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Centimeters',
                'short_code' => 'CM',
                'is_base' => false,
                'ratio_to_base' => 0.01,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Feet',
                'short_code' => 'FT',
                'is_base' => false,
                'ratio_to_base' => 0.3048,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Inches',
                'short_code' => 'IN',
                'is_base' => false,
                'ratio_to_base' => 0.0254,
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Yards',
                'short_code' => 'YD',
                'is_base' => false,
                'ratio_to_base' => 0.9144,
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($systemUnitsData as $uData) {
            $unit = Unit::where('unit_group_id', $lengthGroup->id)
                ->where(function ($q) use ($uData) {
                    $q->where('short_code', $uData['short_code'])
                      ->orWhere('name', $uData['name'])
                      ->orWhere('short_code', strtolower($uData['short_code']));
                })->first();

            if ($unit) {
                $unit->update([
                    'name' => $uData['name'],
                    'short_code' => $uData['short_code'],
                    'is_base' => $uData['is_base'],
                    'ratio_to_base' => $uData['ratio_to_base'],
                    'is_active' => true,
                    'is_system' => true,
                ]);
            } else {
                Unit::create(array_merge($uData, ['unit_group_id' => $lengthGroup->id]));
            }
        }

        // 3. Backfill fabric_widths records to set unit_id
        $lengthUnits = Unit::where('unit_group_id', $lengthGroup->id)->get();
        $inchUnit = $lengthUnits->first(fn($u) => in_array(strtoupper($u->short_code), ['IN', 'INCH', 'INCHES']));
        $cmUnit = $lengthUnits->first(fn($u) => in_array(strtoupper($u->short_code), ['CM', 'CENTIMETER']));
        $meterUnit = $lengthUnits->first(fn($u) => in_array(strtoupper($u->short_code), ['M', 'METER', 'METERS']));
        $feetUnit = $lengthUnits->first(fn($u) => in_array(strtoupper($u->short_code), ['FT', 'FIT', 'FEET', 'FOOT']));

        FabricWidth::all()->each(function (FabricWidth $fw) use ($inchUnit, $cmUnit, $meterUnit, $feetUnit) {
            if (!$fw->unit_id && $fw->unit) {
                $uStr = strtolower(trim($fw->unit));
                $targetUnit = null;

                if (str_contains($uStr, 'inch') || $uStr === 'in' || $uStr === '"') {
                    $targetUnit = $inchUnit;
                } elseif (str_contains($uStr, 'cm') || str_contains($uStr, 'centimeter')) {
                    $targetUnit = $cmUnit;
                } elseif (str_contains($uStr, 'meter') || $uStr === 'm') {
                    $targetUnit = $meterUnit;
                } elseif (str_contains($uStr, 'ft') || str_contains($uStr, 'fit') || str_contains($uStr, 'feet')) {
                    $targetUnit = $feetUnit;
                }

                if ($targetUnit) {
                    $fw->unit_id = $targetUnit->id;
                    $fw->unit = $targetUnit->short_code;
                    $fw->save();
                }
            }
        });
    }
}
