<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Services\FabricCuttingAreaService;

class FabricCuttingAreaWastageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_fabric_cutting_area_calculation_and_auto_wastage()
    {
        // 1. Setup raw material category and fabric raw material (Width: 10 meters)
        $cat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric Material', 'unit_type' => 'length_based', 'status' => 'active']
        );

        $lengthGroup = UnitGroup::where('name', 'Length')->first();
        $metersUnit = Unit::where('name', 'Meters')->first();
        $cmsUnit = Unit::where('name', 'Centimeters')->first();

        $fabric = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'unit_group_id' => $lengthGroup->id,
            'unit_id' => $metersUnit->id,
            'name' => 'Wide Cotton Fabric',
            'unit' => 'Meters',
            'standard_width' => 10.0, // 10 meters wide
            'width_unit' => 'Meters',
            'is_active' => true,
        ]);

        // 2. Create manufacturing product (2m x 2m bedsheet = 4 m^2 per piece)
        $mCategory = ManufacturingProductCategory::create([
            'name' => 'Bedsheets',
            'status' => 'active',
        ]);

        $bedsheet = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'King Bedsheet',
            'is_fabric_used' => true,
            'standard_fabric_length' => 2.0, // 2 meters
            'standard_fabric_width' => 2.0, // 2 meters
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Meters',
        ]);

        // 3. Cut 10 meters from roll -> Total Cut Area = 10m * 10m = 100 m^2
        $cutLength = 10.0;
        $cutArea = FabricCuttingAreaService::calculateCutArea($cutLength, $fabric);
        $this->assertEquals(100.0, $cutArea);

        // 4. Record 20 pieces of bedsheets -> Used Area = 20 * 4 m^2 = 80 m^2
        $outputs = [
            [
                'manufacturing_product_id' => $bedsheet->id,
                'quantity' => 20,
            ]
        ];

        $breakdown = FabricCuttingAreaService::computeCuttingBreakdown($cutLength, $fabric, $outputs);

        $this->assertEquals(100.0, $breakdown['cut_area_base']);
        $this->assertEquals(80.0, $breakdown['used_area_base']);
        $this->assertEquals(20.0, $breakdown['remaining_area_base']);
        // Auto wastage length = Remaining Area (20 m^2) / Fabric Width (10m) = 2.0 meters wastage!
        $this->assertEquals(2.0, $breakdown['wastage_length']);
        $this->assertFalse($breakdown['is_over_capacity']);
        $this->assertEquals(25, $breakdown['max_quantities'][$bedsheet->id]); // Max 100 / 4 = 25 pcs
    }

    public function test_fabric_cutting_area_blocks_over_capacity()
    {
        $cat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric Material', 'unit_type' => 'length_based', 'status' => 'active']
        );

        $lengthGroup = UnitGroup::where('name', 'Length')->first();
        $metersUnit = Unit::where('name', 'Meters')->first();

        $fabric = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'unit_group_id' => $lengthGroup->id,
            'unit_id' => $metersUnit->id,
            'name' => 'Standard Fabric',
            'unit' => 'Meters',
            'standard_width' => 10.0,
            'width_unit' => 'Meters',
            'is_active' => true,
        ]);

        $mCategory = ManufacturingProductCategory::create([
            'name' => 'Bedsheets',
            'status' => 'active',
        ]);

        $bedsheet = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'King Bedsheet',
            'is_fabric_used' => true,
            'standard_fabric_length' => 2.0,
            'standard_fabric_width' => 2.0,
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Meters',
        ]);

        // Cut 10m (100 m^2). Try to produce 60 pieces (60 * 4 = 240 m^2)
        $cutLength = 10.0;
        $outputs = [
            [
                'manufacturing_product_id' => $bedsheet->id,
                'quantity' => 60,
            ]
        ];

        $breakdown = FabricCuttingAreaService::computeCuttingBreakdown($cutLength, $fabric, $outputs);

        $this->assertTrue($breakdown['is_over_capacity']);
        $this->assertEquals(140.0, $breakdown['over_capacity_diff_base']);
        $this->assertEquals(25, $breakdown['max_quantities'][$bedsheet->id]);
    }
}
