<?php

namespace Tests\Feature;

use App\Models\Labor;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ProductionJob;
use App\Models\ProductionBatch;
use App\Models\JobLaborAllocation;
use App\Models\User;
use App\Services\Manufacturing\LaborWageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaborPatternTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('super_admin');
    }

    public function test_labor_wage_service_records_pattern_id_and_uses_pattern_task_rate()
    {
        $product = ManufacturingProduct::create([
            'name' => 'King Size Sheet',
            'product_code' => 'SKU-KS-01',
            'status' => true,
        ]);

        $pattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $product->id,
            'name' => 'Striped Luxury 300TC',
            'standard_labor_rate' => 25.00,
            'is_default' => true,
        ]);

        $task = Task::create([
            'name' => 'Stitching Stage',
            'code' => 'TASK-STITCH',
            'status' => true,
            'default_rate_per_piece' => 10.00,
        ]);

        // Attach task to pattern with pattern-specific rate
        $pattern->tasks()->attach($task->id, [
            'sequence_number' => 1,
            'standard_labor_rate' => 35.00,
        ]);

        $labor = Labor::create([
            'name' => 'Ramesh Master',
            'code' => 'LAB-001',
            'payment_method' => 'job_work',
            'status' => true,
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-9901',
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'target_quantity' => 100,
            'status' => 'in_progress',
        ]);

        $service = app(LaborWageService::class);
        $response = $service->processAllocations(
            allocations: [
                [
                    'labor_id' => $labor->id,
                    'quantity' => 50,
                    'pattern_id' => $pattern->id,
                ]
            ],
            jobId: $job->job_code,
            manufacturingProductId: $product->id,
            taskId: $task->id,
            productionBatchId: 'BATCH-2026-01'
        );

        $this->assertEquals(200, $response->getStatusCode());

        $allocation = JobLaborAllocation::first();
        $this->assertNotNull($allocation);
        $this->assertEquals($pattern->id, $allocation->pattern_id);
        $this->assertEquals($product->id, $allocation->manufacturing_product_id);
        $this->assertEquals(35.00, (float) $allocation->base_rate);
        $this->assertEquals(1750.00, (float) $allocation->calculated_wage);
    }

    public function test_tracking_history_api_returns_pattern_details()
    {
        $product = ManufacturingProduct::create([
            'name' => 'Standard Pillowcase',
            'product_code' => 'SKU-PC-01',
            'status' => true,
        ]);

        $pattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $product->id,
            'name' => 'Embroidered Border',
            'standard_labor_rate' => 18.00,
            'is_default' => true,
        ]);

        $task = Task::create([
            'name' => 'Hemming',
            'code' => 'TASK-HEM',
            'status' => true,
        ]);

        $labor = Labor::create([
            'name' => 'Sita Tailor',
            'code' => 'LAB-002',
            'payment_method' => 'job_work',
            'status' => true,
        ]);

        JobLaborAllocation::create([
            'production_batch_id' => 'BATCH-TEST',
            'job_id' => 'JOB-2026-100',
            'labor_id' => $labor->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'task_id' => $task->id,
            'quantity_processed' => 20,
            'base_rate' => 18.00,
            'calculated_wage' => 360.00,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/admin/production/tracking-history');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.pattern.id', $pattern->id)
            ->assertJsonPath('data.0.pattern.name', 'Embroidered Border');
    }

    public function test_worker_wages_api_returns_pattern_details()
    {
        $product = ManufacturingProduct::create([
            'name' => 'Duvet Cover',
            'product_code' => 'SKU-DC-01',
            'status' => true,
        ]);

        $pattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $product->id,
            'name' => 'Zippered Satin',
            'standard_labor_rate' => 40.00,
        ]);

        $task = Task::create([
            'name' => 'Zipper Attachment',
            'code' => 'TASK-ZIP',
            'status' => true,
        ]);

        $labor = Labor::create([
            'name' => 'Karan Worker',
            'code' => 'LAB-003',
            'payment_method' => 'job_work',
            'status' => true,
        ]);

        JobLaborAllocation::create([
            'production_batch_id' => 'BATCH-DC',
            'job_id' => 'JOB-2026-DC1',
            'labor_id' => $labor->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'task_id' => $task->id,
            'quantity_processed' => 10,
            'base_rate' => 40.00,
            'calculated_wage' => 400.00,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/admin/wages/worker/{$labor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.pattern.id', $pattern->id)
            ->assertJsonPath('data.0.pattern.name', 'Zippered Satin');
    }
}
