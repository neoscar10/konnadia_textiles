<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Services\FabricCuttingAreaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAlterationValidationAndBatchFixTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ManufacturingProduct $largeProduct;
    protected ManufacturingProduct $smallProduct;
    protected Task $cuttingTask;
    protected ProductionJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('super_admin');
        $this->actingAs($this->user);

        $this->cuttingTask = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-CUT-TEST',
            'status' => true,
            'is_labor_required' => true,
            'sequence_number' => 1,
        ]);

        // Bedsheet (Large product)
        $this->largeProduct = ManufacturingProduct::create([
            'name' => 'Bedsheet King Size',
            'code' => 'PROD-BED-KING',
            'is_fabric_used' => true,
            'standard_fabric_length' => 2.5,
            'fabric_length_unit' => 'Meters',
            'standard_fabric_width' => 2.0,
            'fabric_width_unit' => 'Meters',
            'status' => true,
        ]);

        // Pillowcase (Small product)
        $this->smallProduct = ManufacturingProduct::create([
            'name' => 'Pillowcase Standard',
            'code' => 'PROD-PIL-STD',
            'is_fabric_used' => true,
            'standard_fabric_length' => 0.6,
            'fabric_length_unit' => 'Meters',
            'standard_fabric_width' => 0.4,
            'fabric_width_unit' => 'Meters',
            'status' => true,
        ]);

        $this->largeProduct->tasks()->attach($this->cuttingTask->id, ['sequence_number' => 1]);
        $this->smallProduct->tasks()->attach($this->cuttingTask->id, ['sequence_number' => 1]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-0012',
            'manufacturing_product_id' => $this->largeProduct->id,
            'planned_quantity' => 10,
            'status' => 'In Progress',
            'supervisor_id' => $this->user->id,
        ]);

        $this->job = ProductionJob::create([
            'job_code' => 'JOB-2026-0012',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $this->largeProduct->id,
            'task_id' => $this->cuttingTask->id,
            'target_quantity' => 10,
            'status' => 'in_progress',
        ]);

        $this->job->ensureStageExecutionsExist();
    }

    public function test_cannot_alter_small_product_to_larger_product_area()
    {
        // Change job product to Pillowcase (small)
        $this->job->update(['manufacturing_product_id' => $this->smallProduct->id]);

        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $this->job->id])
            ->set('selectedTaskId', $this->cuttingTask->id)
            ->set('alterationRecords', [
                [
                    'source_product_id' => $this->smallProduct->id,
                    'source_quantity' => 1,
                    'target_product_id' => $this->largeProduct->id, // Pillowcase -> Bedsheet (Invalid!)
                    'target_quantity' => 1,
                ]
            ])
            ->call('saveJobAlteration')
            ->assertHasErrors(['alterationRecords.0.target_product_id']);
    }

    public function test_can_alter_large_product_to_smaller_product_and_handles_duplicate_batch_codes()
    {
        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $this->job->id])
            ->set('selectedTaskId', $this->cuttingTask->id)
            ->set('alterationRecords', [
                [
                    'source_product_id' => $this->largeProduct->id,
                    'source_quantity' => 2,
                    'target_product_id' => $this->smallProduct->id, // Bedsheet -> Pillowcase (Valid!)
                    'target_quantity' => 2,
                ]
            ])
            ->call('saveJobAlteration')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('production_batches', [
            'parent_batch_id' => $this->job->production_batch_db_id,
            'manufacturing_product_id' => $this->smallProduct->id,
            'planned_quantity' => 2,
        ]);
    }
}
