<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\JobProductionOutput;
use App\Models\JobWastage;
use App\Models\JobAlteration;
use App\Services\Manufacturing\ProductionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskSequenceAndWorkflowProgressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $category;
    protected ManufacturingProduct $product;
    protected Task $taskCutting;
    protected Task $taskStitching;
    protected Task $taskPacking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->category = ManufacturingProductCategory::create(['name' => 'Bedsheets', 'code' => 'CAT-001', 'status' => true]);

        $this->taskCutting = Task::create(['name' => 'Cutting', 'code' => 'TSK-001', 'status' => true]);
        $this->taskStitching = Task::create(['name' => 'Stitching', 'code' => 'TSK-002', 'status' => true]);
        $this->taskPacking = Task::create(['name' => 'Packing', 'code' => 'TSK-003', 'status' => true]);

        $this->product = ManufacturingProduct::create([
            'name' => 'Premium Sheet',
            'code' => 'MP-2026-9001',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'standard_labor_rate' => 15.00,
        ]);
    }

    /** @test */
    public function it_saves_product_routing_task_sequence_with_custom_labor_rates_and_final_step()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Factory\AddManufacturingProductForm::class, ['id' => $this->product->id])
            ->set('routingTasksList', [
                ['task_id' => (string)$this->taskCutting->id, 'standard_labor_rate' => '20.00', 'is_final_step' => false],
                ['task_id' => (string)$this->taskStitching->id, 'standard_labor_rate' => '25.00', 'is_final_step' => false],
                ['task_id' => (string)$this->taskPacking->id, 'standard_labor_rate' => '10.00', 'is_final_step' => true],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->product->refresh();
        $tasks = $this->product->tasks;

        $this->assertCount(3, $tasks);
        $this->assertEquals($this->taskCutting->id, $tasks[0]->id);
        $this->assertEquals(1, $tasks[0]->pivot->sequence_number);
        $this->assertEquals(20.00, (float)$tasks[0]->pivot->standard_labor_rate);
        $this->assertFalse((bool)$tasks[0]->pivot->is_final_step);

        $this->assertEquals($this->taskPacking->id, $tasks[2]->id);
        $this->assertEquals(3, $tasks[2]->pivot->sequence_number);
        $this->assertTrue((bool)$tasks[2]->pivot->is_final_step);
    }

    /** @test */
    public function it_auto_corrects_routing_to_have_exactly_one_final_step()
    {
        $this->actingAs($this->admin);

        // Submit form with NO final step checked
        Livewire::test(\App\Livewire\Factory\AddManufacturingProductForm::class, ['id' => $this->product->id])
            ->set('routingTasksList', [
                ['task_id' => (string)$this->taskCutting->id, 'standard_labor_rate' => '15.00', 'is_final_step' => false],
                ['task_id' => (string)$this->taskPacking->id, 'standard_labor_rate' => '15.00', 'is_final_step' => false],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->product->refresh();
        $finalTask = $this->product->getFinalTask();

        $this->assertNotNull($finalTask);
        $this->assertEquals($this->taskPacking->id, $finalTask->id, 'Last step should be auto-defaulted as final step.');
    }

    /** @test */
    public function it_spawns_only_first_task_job_when_initiating_production_batch()
    {
        // Setup 3-step routing
        $this->product->tasks()->sync([
            $this->taskCutting->id => ['sequence_number' => 1, 'standard_labor_rate' => 20.00, 'is_final_step' => false],
            $this->taskStitching->id => ['sequence_number' => 2, 'standard_labor_rate' => 25.00, 'is_final_step' => false],
            $this->taskPacking->id => ['sequence_number' => 3, 'standard_labor_rate' => 10.00, 'is_final_step' => true],
        ]);

        $workflowService = new ProductionWorkflowService();
        $response = $workflowService->initiateBatch($this->product->id, $this->admin->id, 500);
        $data = $response->getData(true)['data'];

        $batch = ProductionBatch::find($data['batch']['id']);
        $jobs = $batch->jobs;

        $this->assertCount(1, $jobs, 'ONLY the first task job should be created on batch initiation.');
        $this->assertEquals($this->taskCutting->id, $jobs->first()->task_id);
        $this->assertEquals(500, $jobs->first()->target_quantity);
    }

    /** @test */
    public function it_completes_job_and_automatically_spawns_next_job_with_forward_quantity()
    {
        // Setup 2-step routing: Cutting -> Packing (Final)
        $this->product->tasks()->sync([
            $this->taskCutting->id => ['sequence_number' => 1, 'standard_labor_rate' => 20.00, 'is_final_step' => false],
            $this->taskPacking->id => ['sequence_number' => 2, 'standard_labor_rate' => 10.00, 'is_final_step' => true],
        ]);

        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->product->id, $this->admin->id, 100);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job1 = $batch->jobs->first();

        // Log outputs: Produced 100, Wasted 5, Altered 3 -> Valid Forward = 100 - 5 - 3 = 92
        JobProductionOutput::create([
            'job_code' => $job1->job_code,
            'production_job_id' => $job1->id,
            'manufacturing_product_id' => $this->product->id,
            'task_id' => $this->taskCutting->id,
            'quantity_produced' => 100,
        ]);

        JobWastage::create([
            'job_code' => $job1->job_code,
            'production_job_id' => $job1->id,
            'manufacturing_product_id' => $this->product->id,
            'task_id' => $this->taskCutting->id,
            'quantity_wasted' => 5,
            'reason' => 'Defective cut',
        ]);

        // Alteration requires a child batch
        $childBatch = ProductionBatch::create([
            'parent_batch_id' => $batch->id,
            'batch_date' => now()->format('Y-m-d'),
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 3,
            'priority' => 'Normal',
            'status' => 'Created',
        ]);

        JobAlteration::create([
            'job_code' => $job1->job_code,
            'production_job_id' => $job1->id,
            'source_product_id' => $this->product->id,
            'source_quantity' => 3,
            'target_product_id' => $this->product->id,
            'target_quantity' => 3,
            'child_production_batch_id' => $childBatch->id,
        ]);

        // Complete Job 1
        $compResponse = $workflowService->completeJob($job1->id);
        $compData = $compResponse->getData(true)['data'];

        $job1->refresh();
        $this->assertEquals('completed', $job1->status);

        // Verify downstream Job 2 auto-generated
        $batch->refresh();
        $jobs = $batch->jobs;

        $this->assertCount(2, $jobs, 'Downstream Job 2 should be auto-created upon completing Job 1.');
        $job2 = $jobs->where('task_id', $this->taskPacking->id)->first();
        $this->assertNotNull($job2);
        $this->assertEquals(92, $job2->target_quantity, 'Target quantity for Job 2 must equal (100 Produced - 5 Wasted - 3 Altered) = 92.');
    }

    /** @test */
    public function it_marks_batch_as_completed_when_final_step_job_is_completed()
    {
        // Setup 1-step routing: Packing (Final Step)
        $this->product->tasks()->sync([
            $this->taskPacking->id => ['sequence_number' => 1, 'standard_labor_rate' => 10.00, 'is_final_step' => true],
        ]);

        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->product->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job1 = $batch->jobs->first();

        // Complete final step job
        $compResponse = $workflowService->completeJob($job1->id);
        $compData = $compResponse->getData(true)['data'];

        $this->assertTrue((bool)$compData['isFinalStep']);

        $batch->refresh();
        $this->assertEquals('Completed', $batch->status, 'Batch status must update to Completed upon completing the final step job.');
    }
}
