<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\JobMaterialConsumption;
use App\Models\JobLaborAllocation;
use App\Services\FabricCuttingAreaService;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Services\Manufacturing\ProductionCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class CuttingFabricCostingAndAlterationAreaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ManufacturingProduct $bedsheet;
    protected ManufacturingProduct $pillowcase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create Bedsheet (Larger product: 2.5m x 2.0m = 5.0 m^2)
        $this->bedsheet = ManufacturingProduct::create([
            'name'                   => 'Bedsheet Master Item',
            'code'                   => 'MP-BED-001',
            'standard_fabric_length' => 2.5,
            'standard_fabric_width'  => 200, // 200 cm = 2.0 m
            'fabric_length_unit'     => 'Meters',
            'fabric_width_unit'      => 'Centimeters',
            'status'                 => 'active',
        ]);

        // Create Pillow Case (Smaller product: 0.7m x 50cm = 0.35 m^2)
        $this->pillowcase = ManufacturingProduct::create([
            'name'                   => 'Pillow Case Item',
            'code'                   => 'MP-PIL-001',
            'standard_fabric_length' => 0.7,
            'standard_fabric_width'  => 50, // 50 cm = 0.5 m
            'fabric_length_unit'     => 'Meters',
            'fabric_width_unit'      => 'Centimeters',
            'status'                 => 'active',
        ]);
    }

    public function test_surface_area_calculation_for_products()
    {
        $bedsheetArea = FabricCuttingAreaService::calculateProductPatternAreaM2($this->bedsheet);
        $pillowArea   = FabricCuttingAreaService::calculateProductPatternAreaM2($this->pillowcase);

        $this->assertEquals(5.0, $bedsheetArea);
        $this->assertEquals(0.35, $pillowArea);
        $this->assertTrue($bedsheetArea > $pillowArea);
    }

    public function test_alteration_allowed_from_larger_to_smaller_product()
    {
        $workflowService = resolve(ProductionWorkflowService::class);

        $batch = ProductionBatch::create([
            'batch_code'               => 'PB-TEST-001',
            'manufacturing_product_id' => $this->bedsheet->id,
            'planned_quantity'         => 10,
            'status'                   => 'In Progress',
        ]);

        $job = ProductionJob::create([
            'job_code'                 => 'JOB-TEST-001',
            'production_batch_db_id'   => $batch->id,
            'production_batch_id'      => $batch->batch_code,
            'manufacturing_product_id' => $this->bedsheet->id,
            'target_quantity'          => 10,
            'status'                   => 'in_progress',
        ]);

        // Alter Bedsheet (5.0 m^2) to Pillow Case (0.35 m^2) -> SHOULD SUCCEED
        $alteration = $workflowService->recordJobAlteration(
            job: $job,
            sourceProductId: $this->bedsheet->id,
            sourceQty: 2,
            targetProductId: $this->pillowcase->id,
            targetQty: 2,
            reason: 'Convert surplus bedsheet fabric to pillow case'
        );

        $this->assertNotNull($alteration);
        $this->assertEquals($this->bedsheet->id, $alteration->source_product_id);
        $this->assertEquals($this->pillowcase->id, $alteration->target_product_id);
    }

    public function test_alteration_blocked_from_smaller_to_larger_product()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("surface area is larger than source product");

        $workflowService = resolve(ProductionWorkflowService::class);

        $batch = ProductionBatch::create([
            'batch_code'               => 'PB-TEST-002',
            'manufacturing_product_id' => $this->pillowcase->id,
            'planned_quantity'         => 10,
            'status'                   => 'In Progress',
        ]);

        $job = ProductionJob::create([
            'job_code'                 => 'JOB-TEST-002',
            'production_batch_db_id'   => $batch->id,
            'production_batch_id'      => $batch->batch_code,
            'manufacturing_product_id' => $this->pillowcase->id,
            'target_quantity'          => 10,
            'status'                   => 'in_progress',
        ]);

        // Attempting to alter Pillow Case (0.35 m^2) to Bedsheet (5.0 m^2) -> SHOULD THROW EXCEPTION
        $workflowService->recordJobAlteration(
            job: $job,
            sourceProductId: $this->pillowcase->id,
            sourceQty: 1,
            targetProductId: $this->bedsheet->id,
            targetQty: 1,
            reason: 'Invalid alteration attempt'
        );
    }

    public function test_cutting_fabric_costing_breakdown_computation()
    {
        $catFab = RawMaterialCategory::firstOrCreate(['code' => 'CAT-FAB'], ['name' => 'Fabric Category']);

        $fabric = RawMaterial::create([
            'name'                    => 'Cotton Sheeting Fabric',
            'code'                    => 'RM-FAB-001',
            'raw_material_category_id'=> $catFab->id,
            'standard_width'          => 200,
            'width_unit'              => 'Centimeters',
            'unit'                    => 'Meters',
            'status'                  => 'active',
        ]);

        $productOutputs = [
            [
                'manufacturing_product_id' => $this->bedsheet->id,
                'planned_quantity'         => 10,
            ]
        ];

        // Cut length = 30m, Standard required = 25m (10 pcs x 2.5m), Purchase rate = ₹100/m
        $breakdown = FabricCuttingAreaService::computeCuttingBreakdown(
            cutLength: 30.0,
            rawMaterial: $fabric,
            productOutputs: $productOutputs,
            purchaseRate: 100.0
        );

        $this->assertEquals(30.0, $breakdown['cut_length']);
        $this->assertEquals(25.0, $breakdown['standard_required_length']);
        $this->assertEquals(5.0, $breakdown['wastage_length']);
        $this->assertEquals(500.0, $breakdown['total_wastage_cost']);
        $this->assertEquals(3000.0, $breakdown['total_fabric_cut_cost']);
        $this->assertArrayHasKey($this->bedsheet->id, $breakdown['product_details']);
        $this->assertEquals(3000.0, $breakdown['product_details'][$this->bedsheet->id]['total_fabric_cost']);
    }

    public function test_final_job_completion_cost_summary_tracks_subsidiary_and_cutting_wastage()
    {
        $catFab = RawMaterialCategory::firstOrCreate(['code' => 'CAT-FAB'], ['name' => 'Fabric Category']);
        $catSub = RawMaterialCategory::firstOrCreate(['code' => 'CAT-SUB'], ['name' => 'Subsidiary Category']);

        $fabricMaterial = RawMaterial::create([
            'name'                    => 'Cotton Sheeting Fabric',
            'code'                    => 'RM-FAB-002',
            'raw_material_category_id'=> $catFab->id,
            'unit'                    => 'Meters',
            'status'                  => 'active',
        ]);

        $subsidiaryMaterial = RawMaterial::create([
            'name'                    => 'Elastic Band 1-inch',
            'code'                    => 'RM-SUB-001',
            'raw_material_category_id'=> $catSub->id,
            'unit'                    => 'Meters',
            'status'                  => 'active',
        ]);

        $invFabBatch = InventoryBatch::create([
            'raw_material_id'   => $fabricMaterial->id,
            'batch_number'      => 'BAT-FAB-101',
            'purchase_rate'     => 120.00,
            'received_quantity' => 100,
            'balance_quantity'  => 100,
        ]);

        $invSubBatch = InventoryBatch::create([
            'raw_material_id'   => $subsidiaryMaterial->id,
            'batch_number'      => 'BAT-SUB-101',
            'purchase_rate'     => 15.00,
            'received_quantity' => 200,
            'balance_quantity'  => 200,
        ]);

        $batch = ProductionBatch::create([
            'batch_code'               => 'PB-TEST-COST-01',
            'manufacturing_product_id' => $this->bedsheet->id,
            'planned_quantity'         => 10,
            'status'                   => 'Completed',
        ]);

        $job = ProductionJob::create([
            'job_code'                 => 'JOB-TEST-COST-01',
            'production_batch_db_id'   => $batch->id,
            'production_batch_id'      => $batch->batch_code,
            'manufacturing_product_id' => $this->bedsheet->id,
            'target_quantity'          => 10,
            'status'                   => 'completed',
        ]);

        // Record fabric material consumption (25m @ ₹120 = ₹3000)
        JobMaterialConsumption::create([
            'job_code'          => $job->job_code,
            'production_job_id'  => $job->id,
            'inventory_batch_id'=> $invFabBatch->id,
            'quantity_consumed' => 25.0,
            'unit_cost'         => 120.00,
            'total_cost'        => 3000.00,
        ]);

        // Record subsidiary material consumption (20m @ ₹15 = ₹300)
        JobMaterialConsumption::create([
            'job_code'          => $job->job_code,
            'production_job_id'  => $job->id,
            'inventory_batch_id'=> $invSubBatch->id,
            'quantity_consumed' => 20.0,
            'unit_cost'         => 15.00,
            'total_cost'        => 300.00,
        ]);

        // Create task & labor worker
        $task = \App\Models\Task::create([
            'name'   => 'Stitching Task',
            'code'   => 'TASK-STITCH',
            'status' => true,
        ]);

        $labor = \App\Models\Labor::create([
            'name'        => 'Test Worker',
            'worker_code' => 'W-101',
            'status'      => true,
        ]);

        // Record labor wage (10 pcs @ ₹50 = ₹500)
        JobLaborAllocation::create([
            'job_id'              => $job->job_code,
            'production_batch_id' => $batch->batch_code,
            'task_id'             => $task->id,
            'labor_id'            => $labor->id,
            'rate_type'           => 'piece_rate',
            'rate_applied'        => 50.00,
            'quantity_processed'  => 10,
            'calculated_wage'     => 500.00,
            'status'              => 'approved',
        ]);

        $costingService = resolve(ProductionCostingService::class);
        $summary = $costingService->getJobCostSummary($job->id);

        $this->assertEquals(3000.00, $summary['fabric_cost']);
        $this->assertEquals(300.00, $summary['subsidiary_cost']);
        $this->assertEquals(500.00, $summary['total_labor_cost']);
        $this->assertEquals(3800.00, $summary['total_manufacturing_cost']); // 3000 + 300 + 500
        $this->assertEquals(380.00, $summary['average_cost_per_unit']); // 3800 / 10 pcs
    }
}
