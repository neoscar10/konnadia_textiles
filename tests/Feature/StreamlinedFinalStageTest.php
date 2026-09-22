<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\Labor;
use App\Models\JobProductionOutput;
use App\Models\JobMaterialConsumption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StreamlinedFinalStageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $category;
    protected RawMaterialCategory $subCategory;
    protected RawMaterial $buttonMaterial;
    protected InventoryBatch $buttonBatch;
    protected ManufacturingProduct $product;
    protected Task $cuttingTask;
    protected Task $stitchingTask;
    protected ProductionBatch $prodBatch;
    protected ProductionJob $prodJob;
    protected Labor $labor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->subCategory = RawMaterialCategory::create(['name' => 'Subsidiary Raw Material', 'code' => 'CAT-SUB']);
        $this->category = ManufacturingProductCategory::create(['name' => 'Bedsheets', 'code' => 'CAT-001', 'status' => true]);

        $this->buttonMaterial = RawMaterial::create([
            'raw_material_category_id' => $this->subCategory->id,
            'name' => 'Buttons',
            'code' => 'RM-SUB-BT',
            'unit' => 'Pieces',
        ]);

        $this->buttonBatch = InventoryBatch::create([
            'raw_material_id' => $this->buttonMaterial->id,
            'batch_number' => 'BT-SUB-001',
            'received_quantity' => 1000,
            'balance_quantity' => 1000,
            'unit' => 'Pieces',
            'unit_cost' => 0.50,
            'purchase_rate' => 0.50,
        ]);

        $this->product = ManufacturingProduct::create([
            'name' => 'Premium Bedsheet',
            'code' => 'MP-2026-SUB-01',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'is_subsidiary_used' => true,
        ]);

        $this->product->subsidiaryMaterials()->sync([
            $this->buttonMaterial->id => ['consumption_quantity' => 2.0],
        ]);

        $this->cuttingTask = Task::create(['name' => 'Cutting', 'code' => 'TSK-001', 'status' => true]);
        $this->stitchingTask = Task::create(['name' => 'Stitching', 'code' => 'TSK-002', 'status' => true]);

        $this->product->tasks()->attach([
            $this->cuttingTask->id => ['sequence_number' => 1],
            $this->stitchingTask->id => ['sequence_number' => 2, 'is_final_step' => true],
        ]);

        $this->prodBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-STREAMLINE',
            'status' => 'scheduled',
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 20,
        ]);

        $this->prodJob = ProductionJob::create([
            'job_code' => 'JOB-2026-STREAMLINE',
            'production_batch_id' => $this->prodBatch->id,
            'production_batch_db_id' => $this->prodBatch->id,
            'manufacturing_product_id' => $this->product->id,
            'target_quantity' => 20,
            'status' => 'in_progress',
        ]);
        $this->prodJob->ensureStageExecutionsExist();

        // Mark stage 1 (cutting) completed
        $this->prodJob->stageExecutions->firstWhere('sequence_number', 1)->update([
            'status' => 'completed',
            'completed_quantity' => 20,
        ]);

        $this->labor = Labor::create([
            'name' => 'Stitching Worker',
            'worker_code' => 'W-STITCH-01',
            'daily_rate' => 500,
            'piece_rate' => 10,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function non_cutting_stage_wizard_completes_with_auto_output_and_subsidiary_deduction()
    {
        $this->actingAs($this->admin);

        // Load JobStageWizard for the job (active stage is Stitching - Stage 2)
        $component = Livewire::test(\App\Livewire\Factory\JobStageWizard::class, ['id' => $this->prodJob->id])
            ->assertSet('activeStep', 1)
            ->set('laborRows.0.labor_id', $this->labor->id)
            ->set('laborRows.0.processed_qty', 20)
            ->set('laborRows.0.base_rate', 10)
            ->call('completeActiveStage');

        // Check JobProductionOutput record created automatically
        $output = JobProductionOutput::where('production_job_id', $this->prodJob->id)
            ->where('task_id', $this->stitchingTask->id)
            ->first();

        $this->assertNotNull($output);
        $this->assertEquals(20, $output->quantity_produced);

        // Check Inventory Batch Subsidiary stock deducted automatically: 1000 - (20 * 2) = 960
        $this->buttonBatch->refresh();
        $this->assertEquals(960, (float)$this->buttonBatch->balance_quantity);

        // Check JobMaterialConsumption created
        $consumption = JobMaterialConsumption::where('production_job_id', $this->prodJob->id)
            ->where('task_id', $this->stitchingTask->id)
            ->first();

        $this->assertNotNull($consumption);
        $this->assertEquals(40, (float)$consumption->quantity_consumed);

        // Verify stage execution status completed
        $stitchingStage = $this->prodJob->stageExecutions()->where('task_id', $this->stitchingTask->id)->first();
        $this->assertEquals('completed', $stitchingStage->status);
    }
}
