<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProductPattern;
use App\Models\Task;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\JobMaterialConsumption;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Livewire\Factory\JobStageWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionJobProcessEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ManufacturingProduct $product;
    protected ManufacturingProductPattern $pattern;
    protected ProductionBatch $batch;
    protected ProductionJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $cat = ManufacturingProductCategory::create([
            'name'   => 'Bedsheets',
            'status' => true,
        ]);

        $this->product = ManufacturingProduct::create([
            'name'                              => 'Double Bedsheet',
            'manufacturing_product_category_id' => $cat->id,
            'status'                            => 'active',
        ]);

        $this->pattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $this->product->id,
            'name'                     => 'King Size Pattern',
            'fabric_length'            => 2.50,
            'fabric_length_unit'       => 'm',
            'is_default'               => true,
        ]);

        $task1 = Task::create(['name' => 'Cutting', 'code' => 'TSK-CUT', 'status' => true, 'consumes_raw_material' => true]);
        $task2 = Task::create(['name' => 'Stitching', 'code' => 'TSK-STITCH', 'status' => true]);
        $task3 = Task::create(['name' => 'Ironing', 'code' => 'TSK-IRON', 'status' => true]);

        $this->pattern->tasks()->attach([
            $task1->id => ['sequence_number' => 1, 'standard_labor_rate' => 10, 'is_final_step' => false],
            $task2->id => ['sequence_number' => 2, 'standard_labor_rate' => 15, 'is_final_step' => false],
            $task3->id => ['sequence_number' => 3, 'standard_labor_rate' => 5,  'is_final_step' => true],
        ]);

        $workflowService = resolve(ProductionWorkflowService::class);
        $res = $workflowService->initiateBatch(
            productId: $this->product->id,
            supervisorId: null,
            plannedQuantity: 100,
            priority: 'Normal',
            remarks: 'Test Batch',
            batchDate: now()->format('Y-m-d'),
            patternId: $this->pattern->id
        );

        $data = $res->getData(true)['data'];
        $this->batch = ProductionBatch::find($data['batch']['id']);
        $this->job   = ProductionJob::find($data['job']['id']);
    }

    /** @test */
    public function it_can_skip_and_unskip_a_production_job_stage()
    {
        $workflowService = resolve(ProductionWorkflowService::class);
        $stitchingTask   = Task::where('name', 'Stitching')->first();

        // 1. Skip Stitching Stage
        $workflowService->skipStage($this->job->id, $stitchingTask->id);
        $this->job->refresh();

        $stitchingStage = $this->job->stageExecutions->firstWhere('task_id', $stitchingTask->id);
        $this->assertTrue((bool) $stitchingStage->is_skipped);

        // 2. Unskip Stitching Stage via Livewire
        Livewire::test(JobStageWizard::class, ['id' => $this->job->id])
            ->call('unskipStage', $stitchingStage->id);

        $this->job->refresh();
        $stitchingStage = $this->job->stageExecutions->firstWhere('task_id', $stitchingTask->id);

        $this->assertFalse((bool) $stitchingStage->is_skipped);
        $this->assertEquals('in_progress', $stitchingStage->status);
    }

    /** @test */
    public function it_records_fabric_consumption_in_cutting_stage()
    {
        $rawCat = RawMaterialCategory::create([
            'name'      => 'Fabric Material',
            'code'      => 'RMC-FAB',
            'unit_type' => 'length_based',
        ]);

        $rawMaterial = RawMaterial::create([
            'raw_material_category_id' => $rawCat->id,
            'name'                     => 'Cotton Fabric Roll',
            'code'                     => 'FAB-001',
            'unit'                     => 'Meter',
            'status'                   => true,
            'standard_width'           => 44,
            'width_unit'               => 'Inches',
        ]);

        $invBatch = InventoryBatch::create([
            'raw_material_id'   => $rawMaterial->id,
            'batch_number'      => 'BAT-1001',
            'quantity_received' => 500,
            'balance_quantity'  => 500,
            'unit_cost'         => 120.00,
            'purchase_rate'     => 120.00,
        ]);

        $invBale = InventoryBale::create([
            'inventory_batch_id'     => $invBatch->id,
            'bale_number'            => 'BAL-01',
            'declared_length'        => 500,
            'actual_recorded_length' => 500,
            'current_balance_length' => 500,
            'roll_count'             => 1,
            'status'                 => 'opened',
        ]);

        $roll = InventoryBaleRoll::create([
            'inventory_bale_id'      => $invBale->id,
            'roll_number'            => 'Roll 1',
            'initial_length'         => 500,
            'current_balance_length' => 500,
            'status'                 => 'active',
        ]);

        Livewire::test(JobStageWizard::class, ['id' => $this->job->id])
            ->set('selectedFabrics', [
                [
                    'raw_material_id'    => $rawMaterial->id,
                    'fabric_width_id'    => '',
                    'inventory_batch_id' => $invBatch->id,
                    'inventory_bale_id'  => $invBale->id,
                    'selected_rolls'     => [
                        $roll->id => [
                            'roll_id'     => $roll->id,
                            'roll_number' => $roll->roll_number,
                            'max_length'  => 500,
                            'cut_length'  => 150,
                        ]
                    ]
                ]
            ])
            ->call('recordFabricConsumption');

        $this->assertDatabaseHas('job_material_consumptions', [
            'production_job_id' => $this->job->id,
            'quantity_consumed' => 150,
            'total_cost'        => 18000.00,
        ]);

        $roll->refresh();
        $this->assertEquals(350, $roll->current_balance_length);
    }

    /** @test */
    public function it_spawns_alteration_production_job_with_selected_pattern()
    {
        $altProduct = ManufacturingProduct::create([
            'name'                              => 'Pillow Case',
            'manufacturing_product_category_id' => $this->product->manufacturing_product_category_id,
            'status'                            => 'active',
        ]);

        $altPattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $altProduct->id,
            'name'                     => 'Standard Pillow Pattern',
            'fabric_length'            => 0.75,
            'fabric_length_unit'       => 'm',
            'is_default'               => true,
        ]);

        $workflowService = resolve(ProductionWorkflowService::class);
        $alteration = $workflowService->recordJobAlteration(
            job: $this->job,
            sourceProductId: $this->product->id,
            sourceQty: 10,
            targetProductId: $altProduct->id,
            targetQty: 10,
            reason: 'Altered 10 bedsheets to pillow cases',
            targetPatternId: $altPattern->id
        );

        $this->assertNotNull($alteration->child_production_job_id);
        $this->assertEquals($altPattern->id, $alteration->target_pattern_id);

        $childJob = ProductionJob::find($alteration->child_production_job_id);
        $this->assertNotNull($childJob);
        $this->assertEquals($altProduct->id, $childJob->manufacturing_product_id);
        $this->assertEquals($altPattern->id, $childJob->pattern_id);
        $this->assertEquals(10, $childJob->target_quantity);
        $this->assertEquals('in_progress', $childJob->status);
    }

    /** @test */
    public function it_records_scrap_and_damage_wastage_separately()
    {
        $labor = \App\Models\Labor::create([
            'name' => 'Test Ironer',
            'worker_code' => 'W-IRON-01',
            'daily_rate' => 400,
            'piece_rate' => 8,
            'status' => 'active',
        ]);

        $ironingTask = Task::where('name', 'Ironing')->first();
        $ironingStage = $this->job->stageExecutions->firstWhere('task_id', $ironingTask->id);

        Livewire::test(JobStageWizard::class, ['id' => $this->job->id])
            ->call('selectStage', $ironingStage->id)
            ->set('laborRows.0.labor_id', $labor->id)
            ->set('producedQty', 85)
            ->set('scrapQty', 10)
            ->set('scrapNotes', '10 items converted / sold as scrap')
            ->set('damageQty', 5)
            ->set('damageNotes', '5 items severely torn')
            ->call('completeActiveStage');

        $this->assertDatabaseHas('job_wastages', [
            'production_job_id' => $this->job->id,
            'wastage_type'      => 'scrap',
            'quantity_wasted'   => 10,
            'reason'            => '10 items converted / sold as scrap',
        ]);

        $this->assertDatabaseHas('job_wastages', [
            'production_job_id' => $this->job->id,
            'wastage_type'      => 'damage',
            'quantity_wasted'   => 5,
            'reason'            => '5 items severely torn',
        ]);
    }

    /** @test */
    public function it_cannot_proceed_or_complete_stage_without_worker_selected()
    {
        $ironingTask = Task::where('name', 'Ironing')->first();
        $ironingStage = $this->job->stageExecutions->firstWhere('task_id', $ironingTask->id);

        Livewire::test(JobStageWizard::class, ['id' => $this->job->id])
            ->call('selectStage', $ironingStage->id)
            ->set('laborRows.0.labor_id', '')
            ->call('goToStep', 2)
            ->assertHasErrors(['laborRows.0.labor_id'])
            ->assertSet('activeStep', 1)
            ->call('completeActiveStage')
            ->assertHasErrors(['laborRows.0.labor_id']);
    }
}
