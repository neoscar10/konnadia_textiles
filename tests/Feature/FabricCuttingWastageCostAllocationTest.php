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
use App\Models\ProductionJob;
use App\Models\InventoryBatch;
use App\Services\FabricCuttingAreaService;
use App\Services\FabricCostingService;

class FabricCuttingWastageCostAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
    }

    public function test_fabric_cutting_area_weighted_wastage_cost_allocation()
    {
        // 1. Setup fabric raw material
        $cat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric Material', 'unit_type' => 'length_based', 'status' => 'active']
        );

        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first() ?? UnitGroup::where('name', 'like', '%Length%')->first();
        $metersUnit = Unit::where('name', 'Meters')->first();

        $fabric = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'unit_group_id' => $lengthGroup->id,
            'unit_id' => $metersUnit->id,
            'name' => 'Premium Satin Fabric',
            'unit' => 'Meters',
            'standard_width' => 10.0, // 10 meters wide
            'width_unit' => 'Meters',
            'is_active' => true,
        ]);

        $mCategory = ManufacturingProductCategory::create([
            'name' => 'Bedding Sets',
            'status' => 'active',
        ]);

        // Bedsheet: 2m x 2m = 4 m^2 surface area
        $bedsheet = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'King Bedsheet',
            'is_fabric_used' => true,
            'standard_fabric_length' => 2.0,
            'standard_fabric_width' => 2.0,
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Meters',
        ]);

        // Pillowcase: 1m x 1m = 1 m^2 surface area
        $pillowcase = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'Standard Pillowcase',
            'is_fabric_used' => true,
            'standard_fabric_length' => 1.0,
            'standard_fabric_width' => 1.0,
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Meters',
        ]);

        // 2. Cut 10m fabric roll at purchase rate ₹100/m (Total Cut Area = 100 m^2, Cost = ₹1000)
        $cutLength = 10.0;
        $purchaseRate = 100.00;

        // Output: 10 Bedsheets (40 m^2) + 20 Pillowcases (20 m^2) = Total Used Area 60 m^2
        $outputs = [
            [
                'manufacturing_product_id' => $bedsheet->id,
                'quantity' => 10,
            ],
            [
                'manufacturing_product_id' => $pillowcase->id,
                'quantity' => 20,
            ],
        ];

        $breakdown = FabricCuttingAreaService::computeCuttingBreakdown($cutLength, $fabric, $outputs, $purchaseRate);

        $this->assertEquals(100.0, $breakdown['cut_area_base']);
        $this->assertEquals(60.0, $breakdown['used_area_base']);
        $this->assertEquals(40.0, $breakdown['remaining_area_base']);
        $this->assertEquals(4.0, $breakdown['wastage_length']); // 40 m^2 / 10m = 4.0m
        $this->assertEquals(400.00, $breakdown['total_wastage_cost']); // 4.0m * ₹100 = ₹400

        // Check area-weighted allocation
        $bedsheetDetail = $breakdown['product_details'][$bedsheet->id];
        $pillowcaseDetail = $breakdown['product_details'][$pillowcase->id];

        // Bedsheet total area = 40 m^2 (2/3 of output area), Pillowcase total area = 20 m^2 (1/3 of output area)
        // Wastage cost allocation: Bedsheet gets 2/3 of ₹400 = ₹266.67, Pillowcase gets 1/3 of ₹400 = ₹133.33
        $this->assertEquals(266.67, $bedsheetDetail['allocated_wastage_cost']);
        $this->assertEquals(133.33, $pillowcaseDetail['allocated_wastage_cost']);

        // Verify Bedsheet bears higher waste cost per piece than pillowcase
        $this->assertGreaterThan($pillowcaseDetail['wastage_per_piece'], $bedsheetDetail['wastage_per_piece']);
    }

    public function test_fabric_costing_service_persists_area_weighted_wastage_allocation()
    {
        $cat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric Material', 'unit_type' => 'length_based', 'status' => 'active']
        );

        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first() ?? UnitGroup::where('name', 'like', '%Length%')->first();
        $metersUnit = Unit::where('name', 'Meters')->first();

        $fabric = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'unit_group_id' => $lengthGroup->id,
            'unit_id' => $metersUnit->id,
            'name' => 'Cotton Fabric Roll',
            'unit' => 'Meters',
            'standard_width' => 60.0, // 60 inches
            'width_unit' => 'Inches',
            'is_active' => true,
        ]);

        $batch = InventoryBatch::create([
            'raw_material_id' => $fabric->id,
            'batch_number' => 'BATCH-TEST-001',
            'received_quantity' => 100,
            'balance_quantity' => 100,
            'unit_cost' => 120.00,
            'unit' => 'Meters',
            'status' => 'active',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-CUT-TEST-001',
            'target_quantity' => 30,
            'status' => 'in_progress',
            'job_date' => now()->format('Y-m-d'),
        ]);

        $mCategory = ManufacturingProductCategory::create(['name' => 'Linens', 'status' => 'active']);
        $largeProduct = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'Large Sheet',
            'standard_fabric_length' => 2.0,
            'standard_fabric_width' => 60.0,
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Inches',
        ]);
        $smallProduct = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'Small Napkin',
            'standard_fabric_length' => 0.5,
            'standard_fabric_width' => 30.0,
            'fabric_length_unit' => 'Meters',
            'fabric_width_unit' => 'Inches',
        ]);

        $costingService = resolve(FabricCostingService::class);
        $result = $costingService->calculateFabricCostAllocation(
            $job->id,
            $batch->id,
            10.0, // 10m consumed
            [
                ['manufacturing_product_id' => $largeProduct->id, 'quantity' => 4, 'length' => 2.0, 'width' => 60.0],
                ['manufacturing_product_id' => $smallProduct->id, 'quantity' => 8, 'length' => 0.5, 'width' => 30.0],
            ],
            2.0, // 2m wastage (₹240 total wastage cost)
            60.00
        );

        $this->assertEquals(1200.00, $result['total_fabric_cost_consumed']);
        $this->assertEquals(240.00, $result['total_wastage_cost']);
        $this->assertCount(2, $result['itemized_breakdown']);

        // Check persistent JobProductionOutput records
        $largeOutput = \App\Models\JobProductionOutput::where('production_job_id', $job->id)
            ->where('manufacturing_product_id', $largeProduct->id)
            ->first();
        $smallOutput = \App\Models\JobProductionOutput::where('production_job_id', $job->id)
            ->where('manufacturing_product_id', $smallProduct->id)
            ->first();

        $this->assertNotNull($largeOutput);
        $this->assertNotNull($smallOutput);
        $this->assertGreaterThan((float)$smallOutput->allocated_wastage_cost, (float)$largeOutput->allocated_wastage_cost);
    }

    public function test_calculate_live_roll_cut_breakdown_with_unit_conversions()
    {
        $cat = RawMaterialCategory::firstOrCreate(
            ['code' => 'CAT-FAB'],
            ['name' => 'Fabric Material', 'unit_type' => 'length_based', 'status' => 'active']
        );

        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first() ?? UnitGroup::where('name', 'like', '%Length%')->first();
        $metersUnit = Unit::where('name', 'Meters')->first();

        // 36 Inches width fabric
        $fabric = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'unit_group_id' => $lengthGroup->id,
            'unit_id' => $metersUnit->id,
            'name' => '36-Inch Black Fabric',
            'unit' => 'Meters',
            'standard_width' => 36.0,
            'width_unit' => 'Inches',
            'is_active' => true,
        ]);

        $mCategory = ManufacturingProductCategory::create(['name' => 'Shirts', 'status' => 'active']);
        $product = ManufacturingProduct::create([
            'manufacturing_product_category_id' => $mCategory->id,
            'name' => 'Black Shirt',
            'standard_fabric_length' => 2.5, // 2.5m per piece
            'fabric_length_unit' => 'Meters',
        ]);

        // Live calculation for 70 meters cut length on a 36-inch (0.9144m) wide roll with 10 Pcs job target @ ₹100/m
        $live = FabricCuttingAreaService::calculateLiveRollCutBreakdown(
            70.0,
            null,
            $fabric,
            $product,
            10.0, // 10 Pcs target
            100.00 // ₹100/m
        );

        $this->assertEquals(70.0, $live['cut_length']);
        $this->assertEquals(36.0, $live['roll_width_inches']);
        $this->assertEquals(91.4, $live['roll_width_cm']); // 36 * 2.54 = 91.44 cm
        $this->assertEquals(64.01, $live['cut_area_m2']); // 70 * 0.9144 = 64.008 m^2 -> 64.01 m^2
        $this->assertEquals(28, $live['est_yield_pieces']); // 70 / 2.5 = 28 Pcs
        $this->assertEquals(25.0, $live['target_req_length']); // 10 Pcs * 2.5m = 25m
        $this->assertEquals(45.0, $live['wastage_length']); // 70m - 25m = 45m
        $this->assertEquals(4500.00, $live['wastage_cost']); // 45m * ₹100 = ₹4,500
        $this->assertEquals(18, $live['surplus_pieces']); // 28 Pcs - 10 Pcs = 18 Pcs
        $this->assertTrue($live['is_target_met']);
    }
}
