<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ManufacturingProductCategory;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Livewire\Factory\CuttingStageWizard;
use App\Livewire\Admin\Production\JobIndexPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SharedCuttingStageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FactorySupervisor $supervisor;
    protected ManufacturingProduct $product1;
    protected ManufacturingProduct $product2;
    protected RawMaterial $fabric;
    protected InventoryBatch $batch;
    protected InventoryBale $bale;
    protected InventoryBaleRoll $roll1;
    protected Labor $labor;
    protected Task $cuttingTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $mpCat = ManufacturingProductCategory::create(['name' => 'Textile Finished Goods']);
        $this->product1 = ManufacturingProduct::create([
            'name' => 'King Bedsheet',
            'code' => 'MP-BED-001',
            'manufacturing_product_category_id' => $mpCat->id,
            'standard_fabric_length' => 2.5,
            'standard_fabric_width' => 90.0,
            'fabric_width_unit' => 'Inches',
            'fabric_length_unit' => 'Meters',
            'status' => 'active',
        ]);

        $this->product2 = ManufacturingProduct::create([
            'name' => 'Pillowcase Pair',
            'code' => 'MP-PIL-002',
            'manufacturing_product_category_id' => $mpCat->id,
            'standard_fabric_length' => 1.0,
            'standard_fabric_width' => 45.0,
            'fabric_width_unit' => 'Inches',
            'fabric_length_unit' => 'Meters',
            'status' => 'active',
        ]);

        $rmCat = RawMaterialCategory::create(['name' => 'Fabric Category', 'code' => 'CAT-FAB']);
        $this->fabric = RawMaterial::create([
            'name' => 'Neoscar Black Fabric',
            'code' => 'RM-FAB-001',
            'raw_material_category_id' => $rmCat->id,
            'standard_width' => 90.0,
            'width_unit' => 'Inches',
            'unit' => 'Meters',
            'status' => 'active',
        ]);

        $this->batch = InventoryBatch::create([
            'batch_number' => 'BAT-2026-0001',
            'raw_material_id' => $this->fabric->id,
            'received_quantity' => 1000,
            'balance_quantity' => 1000,
            'unit_cost' => 5.0,
        ]);

        $this->bale = InventoryBale::create([
            'inventory_batch_id' => $this->batch->id,
            'bale_number' => 'BALE-2026-0001',
            'declared_length' => 1000,
            'current_balance_length' => 1000,
            'status' => 'opened',
        ]);

        $this->roll1 = InventoryBaleRoll::create([
            'inventory_bale_id' => $this->bale->id,
            'roll_number' => 'ROLL-0001',
            'initial_length' => 1000,
            'current_balance_length' => 1000,
            'status' => 'active',
        ]);

        $this->supervisor = FactorySupervisor::create([
            'name' => 'Joshua Supervisor',
            'phone' => '08099887766',
            'status' => 'active',
        ]);

        $this->labor = Labor::create([
            'name' => 'Cutter John',
            'worker_code' => 'LBR-001',
            'daily_rate' => 150.00,
            'piece_rate' => 15.00,
            'status' => true,
        ]);

        $this->cuttingTask = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-CUT',
            'status' => true,
        ]);

        Task::create([
            'name' => 'Stitching',
            'code' => 'TSK-STITCH',
            'status' => true,
        ]);
    }

    /** @test */
    public function batch_creation_modal_creates_empty_batch_and_redirects_to_shared_cutting()
    {
        $this->actingAs($this->admin);

        Livewire::test(JobIndexPage::class)
            ->set('factory_supervisor_id', $this->supervisor->id)
            ->set('priority', 'Normal')
            ->set('notes', 'Initial empty batch')
            ->call('saveJob')
            ->assertRedirect(route('factory.cutting-stage', ['batch' => ProductionBatch::latest()->first()->batch_code]));

        $batch = ProductionBatch::latest()->first();
        $this->assertEquals('In Cutting', $batch->status);
        $this->assertEquals(0, $batch->planned_quantity);
    }

    /** @test */
    public function shared_cutting_stage_validates_area_capacity_and_spawns_jobs_with_cutting_completed()
    {
        $this->actingAs($this->admin);

        $batch = ProductionBatch::create([
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'In Cutting',
            'planned_quantity' => 0,
        ]);

        $test = Livewire::test(CuttingStageWizard::class, ['batch' => $batch->batch_code])
            ->set('selectedFabrics.0.raw_material_id', $this->fabric->id)
            ->set('selectedFabrics.0.inventory_batch_id', $this->batch->id)
            ->set('selectedFabrics.0.inventory_bale_id', $this->bale->id)
            ->set("selectedFabrics.0.selected_rolls.{$this->roll1->id}", [
                'roll_id' => $this->roll1->id,
                'roll_number' => $this->roll1->roll_number,
                'max_length' => 1000.0,
                'cut_length' => 100.0,
                'products' => [
                    [
                        'manufacturing_product_id' => $this->product1->id,
                        'pattern_id' => null,
                        'planned_quantity' => 20,
                    ],
                    [
                        'manufacturing_product_id' => $this->product2->id,
                        'pattern_id' => null,
                        'planned_quantity' => 10,
                    ],
                ],
            ])
            ->call('goToStep', 2)
            ->assertSet('currentStep', 2)
            ->call('goToStep', 3)
            ->assertSet('currentStep', 3)
            ->call('goToStep', 4)
            ->assertSet('currentStep', 4)
            ->call('submitCuttingStage')
            ->assertRedirect(route('admin.production.batches.jobs', $batch->batch_code));

        $batch->refresh();
        $this->assertEquals('In Progress', $batch->status);

        $jobs = ProductionJob::where('production_batch_db_id', $batch->id)->get();
        $this->assertCount(2, $jobs);

        foreach ($jobs as $job) {
            $stage1 = $job->stageExecutions()->where('task_id', $this->cuttingTask->id)->first();
            $this->assertNotNull($stage1);
            $this->assertEquals('completed', $stage1->status);

            $stage2 = $job->stageExecutions()->where('sequence_number', 2)->first();
            $this->assertNotNull($stage2);
            $this->assertEquals('in_progress', $stage2->status);
        }
    }

    /** @test */
    public function multi_worker_allocation_per_product_in_cutting_stage()
    {
        $this->actingAs($this->admin);

        $labor2 = Labor::create([
            'name' => 'Cutter Jane',
            'worker_code' => 'LBR-002',
            'daily_rate' => 160.00,
            'piece_rate' => 15.00,
            'status' => true,
        ]);

        $batch = ProductionBatch::create([
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'In Cutting',
            'planned_quantity' => 0,
        ]);

        $component = Livewire::test(CuttingStageWizard::class, ['batch' => $batch->batch_code])
            ->set('selectedFabrics.0.raw_material_id', $this->fabric->id)
            ->set('selectedFabrics.0.inventory_batch_id', $this->batch->id)
            ->set('selectedFabrics.0.inventory_bale_id', $this->bale->id)
            ->set("selectedFabrics.0.selected_rolls.{$this->roll1->id}", [
                'roll_id' => $this->roll1->id,
                'roll_number' => $this->roll1->roll_number,
                'max_length' => 1000.0,
                'cut_length' => 100.0,
                'products' => [
                    [
                        'manufacturing_product_id' => $this->product1->id,
                        'pattern_id' => null,
                        'planned_quantity' => 20,
                    ],
                ],
            ])
            ->call('goToStep', 2);

        $key = "{$this->product1->id}_0";

        // Add second worker
        $component->call('addWorkerToProduct', $key);
        $component->set("laborAllocations.{$key}.workers.0.quantity", 12);
        $component->set("laborAllocations.{$key}.workers.1.labor_id", $labor2->id);
        $component->set("laborAllocations.{$key}.workers.1.quantity", 8);

        $component->call('goToStep', 3)
            ->assertSet('currentStep', 3)
            ->call('goToStep', 4)
            ->call('submitCuttingStage');

        $job = ProductionJob::where('production_batch_db_id', $batch->id)->first();
        $this->assertNotNull($job);

        $allocations = \App\Models\JobLaborAllocation::where('job_id', $job->job_code)->get();
        $this->assertCount(2, $allocations);
        $this->assertEquals(20, $allocations->sum('quantity_processed'));
    }
}
