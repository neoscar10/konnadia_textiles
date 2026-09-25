<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Services\Manufacturing\ProductionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SameBatchAlterationAndCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ManufacturingProduct $bedsheet;
    protected ManufacturingProduct $pillowcase;
    protected Task $cuttingTask;
    protected Task $stitchingTask;

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
            'code' => 'TSK-CUT',
            'status' => true,
            'sequence_number' => 1,
        ]);

        $this->stitchingTask = Task::create([
            'name' => 'Stitching',
            'code' => 'TSK-STITCH',
            'status' => true,
            'sequence_number' => 2,
        ]);

        $this->bedsheet = ManufacturingProduct::create([
            'name' => 'Yawa Bedsheet',
            'code' => 'MP-BED-001',
            'status' => 'active',
            'standard_fabric_length' => 2.5,
            'standard_fabric_width' => 2.0,
        ]);

        $this->pillowcase = ManufacturingProduct::create([
            'name' => 'Yawa Pillowcase',
            'code' => 'MP-PIL-001',
            'status' => 'active',
            'standard_fabric_length' => 0.6,
            'standard_fabric_width' => 0.4,
        ]);

        $this->bedsheet->tasks()->attach([
            $this->cuttingTask->id => ['sequence_number' => 1, 'is_final_step' => false],
            $this->stitchingTask->id => ['sequence_number' => 2, 'is_final_step' => true],
        ]);

        $this->pillowcase->tasks()->attach([
            $this->cuttingTask->id => ['sequence_number' => 1, 'is_final_step' => false],
            $this->stitchingTask->id => ['sequence_number' => 2, 'is_final_step' => true],
        ]);
    }

    public function test_alteration_job_is_created_in_same_batch_and_blocks_batch_completion_until_finished()
    {
        $workflowService = new ProductionWorkflowService();

        // 1. Initiate Batch PB-2026-0099 with 10 Bedsheets
        $initRes = $workflowService->initiateBatch($this->bedsheet->id, $this->user->id, 10);
        $initData = $initRes->getData(true)['data'];
        $batch = ProductionBatch::find($initData['batch']['id']);
        $mainJob = ProductionJob::find($initData['job']['id']);

        $this->assertEquals(1, $batch->jobs()->count());
        $this->assertContains($batch->status, ['Created', 'In Progress']);

        // 2. Complete Cutting on Main Job with yield 8, and record alteration of 2 Pcs into Pillowcase
        $workflowService->recordJobAlteration(
            job: $mainJob,
            sourceProductId: $this->bedsheet->id,
            sourceQty: 2,
            targetProductId: $this->pillowcase->id,
            targetQty: 2,
            reason: 'Altered from defective bedsheet corner'
        );

        // Verify that NO new batch was created, but a new job was added inside the SAME batch
        $this->assertDatabaseCount('production_batches', 1);
        $this->assertEquals(2, $batch->jobs()->count(), 'Batch must contain 2 jobs (Main Bedsheet Job + Altered Pillowcase Job)');

        $alterationJob = $batch->jobs()->where('manufacturing_product_id', $this->pillowcase->id)->first();
        $this->assertNotNull($alterationJob);
        $this->assertEquals($batch->batch_code, $alterationJob->production_batch_id);
        $this->assertEquals($batch->id, $alterationJob->production_batch_db_id);

        // 3. Complete main job (Stitching - Final Task)
        $workflowService->completeJob($mainJob->id, $this->cuttingTask->id);
        $workflowService->completeJob($mainJob->id, $this->stitchingTask->id);

        $mainJob->refresh();
        $batch->refresh();

        $this->assertEquals('completed', $mainJob->status);
        $this->assertFalse($batch->isFullyCompleted(), 'Batch must NOT be fully completed while alteration job is still in progress!');
        $this->assertNotEquals('Completed', $batch->status, 'Batch status must remain In Progress!');
        $this->assertFalse($batch->isReadyForConversion(), 'Batch must NOT be ready for storefront conversion!');

        // 4. Complete alteration job (Cutting & Stitching - Final Task)
        $workflowService->completeJob($alterationJob->id, $this->cuttingTask->id);
        $workflowService->completeJob($alterationJob->id, $this->stitchingTask->id);

        $alterationJob->refresh();
        $batch->refresh();

        $this->assertEquals('completed', $alterationJob->status);
        $this->assertTrue($batch->isFullyCompleted(), 'Batch MUST be fully completed now that both main job and alteration job are completed!');
        $this->assertEquals('Completed', $batch->status);
        $this->assertTrue($batch->isReadyForConversion());
    }
}
