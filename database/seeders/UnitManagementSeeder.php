<?php

namespace Database\Seeders;

use App\Models\UnitGroup;
use App\Models\Unit;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use Illuminate\Database\Seeder;

class UnitManagementSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Length Units Group
        $lengthGroup = UnitGroup::firstOrCreate(
            ['code' => 'LENGTH'],
            ['name' => 'Length Units', 'description' => 'Units for measuring fabric length and linear materials', 'is_active' => true]
        );

        $meters = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Meters'], ['short_code' => 'm', 'is_base' => true, 'ratio_to_base' => 1.0, 'is_active' => true]);
        $yards = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Yards'], ['short_code' => 'yd', 'is_base' => false, 'ratio_to_base' => 0.9144, 'is_active' => true]);
        $feet = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Feet'], ['short_code' => 'ft', 'is_base' => false, 'ratio_to_base' => 0.3048, 'is_active' => true]);
        $inches = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Inches'], ['short_code' => 'in', 'is_base' => false, 'ratio_to_base' => 0.0254, 'is_active' => true]);
        $cms = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Centimeters'], ['short_code' => 'cm', 'is_base' => false, 'ratio_to_base' => 0.01, 'is_active' => true]);
        $kms = Unit::firstOrCreate(['unit_group_id' => $lengthGroup->id, 'name' => 'Kilometers'], ['short_code' => 'km', 'is_base' => false, 'ratio_to_base' => 1000.0, 'is_active' => true]);

        // 2. Count & Packaging Units Group
        $countGroup = UnitGroup::firstOrCreate(
            ['code' => 'COUNT'],
            ['name' => 'Count & Packaging Units', 'description' => 'Units for piece count, boxes, rolls, packets and bundles', 'is_active' => true]
        );

        $pieces = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Pieces'], ['short_code' => 'pcs', 'is_base' => true, 'ratio_to_base' => 1.0, 'is_active' => true]);
        $boxes = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Boxes'], ['short_code' => 'box', 'is_base' => false, 'ratio_to_base' => 100.0, 'is_active' => true]);
        $packets = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Packets'], ['short_code' => 'pkt', 'is_base' => false, 'ratio_to_base' => 10.0, 'is_active' => true]);
        $bundles = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Bundles'], ['short_code' => 'bdl', 'is_base' => false, 'ratio_to_base' => 50.0, 'is_active' => true]);
        $rolls = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Rolls'], ['short_code' => 'roll', 'is_base' => false, 'ratio_to_base' => 1.0, 'is_active' => true]);
        $spools = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Spools'], ['short_code' => 'spool', 'is_base' => false, 'ratio_to_base' => 1.0, 'is_active' => true]);
        $cones = Unit::firstOrCreate(['unit_group_id' => $countGroup->id, 'name' => 'Cones'], ['short_code' => 'cone', 'is_base' => false, 'ratio_to_base' => 1.0, 'is_active' => true]);

        // 3. Zip / Fasteners Group
        $zipGroup = UnitGroup::firstOrCreate(
            ['code' => 'ZIP'],
            ['name' => 'Zip & Fastener Units', 'description' => 'Units for zippers, buttons and specialized fasteners', 'is_active' => true]
        );

        Unit::firstOrCreate(['unit_group_id' => $zipGroup->id, 'name' => 'Pieces'], ['short_code' => 'pcs', 'is_base' => true, 'ratio_to_base' => 1.0, 'is_active' => true]);
        Unit::firstOrCreate(['unit_group_id' => $zipGroup->id, 'name' => 'Boxes (100 Pcs)'], ['short_code' => 'box', 'is_base' => false, 'ratio_to_base' => 100.0, 'is_active' => true]);
        Unit::firstOrCreate(['unit_group_id' => $zipGroup->id, 'name' => 'Packets (10 Pcs)'], ['short_code' => 'pkt', 'is_base' => false, 'ratio_to_base' => 10.0, 'is_active' => true]);
        Unit::firstOrCreate(['unit_group_id' => $zipGroup->id, 'name' => 'Tape Meters'], ['short_code' => 'm', 'is_base' => false, 'ratio_to_base' => 1.0, 'is_active' => true]);

        // 4. Weight Units Group
        $weightGroup = UnitGroup::firstOrCreate(
            ['code' => 'WEIGHT'],
            ['name' => 'Weight Units', 'description' => 'Units for measuring weight/mass of materials', 'is_active' => true]
        );

        Unit::firstOrCreate(['unit_group_id' => $weightGroup->id, 'name' => 'Kgs'], ['short_code' => 'kg', 'is_base' => true, 'ratio_to_base' => 1.0, 'is_active' => true]);
        Unit::firstOrCreate(['unit_group_id' => $weightGroup->id, 'name' => 'Grams'], ['short_code' => 'g', 'is_base' => false, 'ratio_to_base' => 0.001, 'is_active' => true]);

        // 5. Volume Units Group
        $volumeGroup = UnitGroup::firstOrCreate(
            ['code' => 'VOLUME'],
            ['name' => 'Volume Units', 'description' => 'Units for measuring liquids and dyes', 'is_active' => true]
        );

        Unit::firstOrCreate(['unit_group_id' => $volumeGroup->id, 'name' => 'Liters'], ['short_code' => 'L', 'is_base' => true, 'ratio_to_base' => 1.0, 'is_active' => true]);

        // Associate existing categories with unit groups
        RawMaterialCategory::whereNull('unit_group_id')->get()->each(function ($cat) use ($lengthGroup, $countGroup) {
            if ($cat->unit_type && $cat->unit_type->value === 'length_based') {
                $cat->update(['unit_group_id' => $lengthGroup->id]);
            } else {
                $cat->update(['unit_group_id' => $countGroup->id]);
            }
        });

        // Associate existing raw materials with unit groups and units
        RawMaterial::whereNull('unit_group_id')->get()->each(function ($mat) use ($lengthGroup, $countGroup) {
            $cat = $mat->category;
            $group = ($cat && $cat->unit_group_id) ? $cat->unitGroup : ($mat->isUnitValidForCategory() ? $lengthGroup : $countGroup);
            if ($group) {
                $unitObj = Unit::where('unit_group_id', $group->id)
                    ->where(function ($q) use ($mat) {
                        $q->where('name', $mat->unit)->orWhere('short_code', $mat->unit);
                    })->first() ?? $group->baseUnit;

                $mat->update([
                    'unit_group_id' => $group->id,
                    'unit_id' => $unitObj ? $unitObj->id : null,
                ]);
            }
        });
    }
}
