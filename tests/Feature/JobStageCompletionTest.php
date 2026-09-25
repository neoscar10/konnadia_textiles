<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\Task;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobStageExecution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobStageCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FactorySupervisor $supervisor;
    protected ManufacturingProduct $product;
    protected Task $taskCutting;
    protected Task $taskIroning;
    protected Task $taskStitching;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->supervisor = FactorySupervisor::create([
            'name' => 'Supervisor John',
            'status' => 'active',
        ]);

        $mpCat = ManufacturingProductCategory::create(['name' => 'Bedding']);
        $this->product = ManufacturingProduct::create([
            'name' => 'King Bedsheet',
            'code' => 'MP-BED-001',
            'manufacturing_product_category_id' => $mpCat->id,
            'status' => 'active',
        ]);

        $this->taskCutting = Task::create(['name' => 'Cutting', 'code' => 'TSK-CUT', 'status' => true]);
        $this->taskIroning = Task::create(['name' => 'Ironing', 'code' => 'TSK-IRON', 'status' => true]);
        $this->taskStitching = Task::create(['name' => 'Stitching', 'code' => 'TSK-STITCH', 'status' => true]);

        $this->product->tasks()->attach([
            $this->taskCutting->id => ['sequence_number' => 1, 'is_final_step' => false],
            $this->taskIroning->id => ['sequence_number' => 2, 'is_final_step' => false],
            $this->taskStitching->id => ['sequence_number' => 3, 'is_final_step' => true],
        ]);
    }

    /** @test */
    public function job_remains_in_progress_when_only_first_stage_is_completed()
    {
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-9999',
            'factory_supervisor_id' => $this->supervisor->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 100,
            'status' => 'In Progress',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-9999',
            'production_batch_id' => $batch->batch_code,
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $this->product->id,
            'target_quantity' => 100,
            'status' => 'in_progress',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskCutting->id,
            'sequence_number' => 1,
            'target_quantity' => 100,
            'completed_quantity' => 100,
            'status' => 'completed',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskIroning->id,
            'sequence_number' => 2,
            'target_quantity' => 100,
            'status' => 'in_progress',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskStitching->id,
            'sequence_number' => 3,
            'target_quantity' => 100,
            'status' => 'pending',
        ]);

        $job->refresh();

        // 1. Status must remain 'in_progress', NOT 'completed'
        $this->assertEquals('in_progress', $job->status);

        // 2. Progress percentage should be 33.3% (1 out of 3 stages completed)
        $this->assertEquals(33.3, $job->progress_percentage);

        // 3. Batch is fully completed must return false
        $this->assertFalse($batch->isFullyCompleted());
    }

    /** @test */
    public function job_completes_and_batch_completes_only_when_all_stages_are_completed()
    {
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-8888',
            'factory_supervisor_id' => $this->supervisor->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 50,
            'status' => 'In Progress',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-8888',
            'production_batch_id' => $batch->batch_code,
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $this->product->id,
            'target_quantity' => 50,
            'status' => 'in_progress',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskCutting->id,
            'sequence_number' => 1,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'status' => 'completed',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskIroning->id,
            'sequence_number' => 2,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'status' => 'completed',
        ]);

        JobStageExecution::create([
            'production_job_id' => $job->id,
            'task_id' => $this->taskStitching->id,
            'sequence_number' => 3,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'status' => 'completed',
        ]);

        $job->refresh();

        // All 3 stages complete => status is 'completed' & progress is 100%
        $this->assertEquals('completed', $job->status);
        $this->assertEquals(100.0, $job->progress_percentage);
        $this->assertTrue($batch->isFullyCompleted());
    }
}
