<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Labor;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductionJobsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin_test_' . uniqid() . '@example.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_can_list_production_jobs_via_api(): void
    {
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-001',
            'status' => 'In Progress',
            'batch_date' => now(),
            'planned_quantity' => 50,
            'target_quantity' => 50,
        ]);

        $task = Task::create([
            'code' => 'TSK-CUT',
            'name' => 'Cutting Stage',
            'sequence_number' => 1,
            'status' => true,
        ]);

        $product = ManufacturingProduct::create([
            'name' => 'King Size Bedsheet',
            'title' => 'King Size Bedsheet',
            'code' => 'MP-BED-001',
            'product_code' => 'MP-BED-001',
            'status' => true,
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-TEST-001',
            'production_batch_id' => $batch->batch_code,
            'production_batch_db_id' => $batch->id,
            'task_id' => $task->id,
            'manufacturing_product_id' => $product->id,
            'target_quantity' => 50,
            'completed_quantity' => 0,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'summary' => ['total_jobs', 'in_progress_jobs', 'completed_jobs'],
                'data',
                'meta',
            ]);
    }

    public function test_can_get_job_options_and_workbench_via_api(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tasks', 'manufacturing_products', 'laborers', 'statuses']]);

        $workbenchResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/workbench');

        $workbenchResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['active_jobs', 'pending_laborer_assignments']);
    }

    public function test_can_show_job_detail_and_assign_laborers_via_api(): void
    {
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-002',
            'status' => 'In Progress',
            'batch_date' => now(),
            'planned_quantity' => 100,
            'target_quantity' => 100,
        ]);

        $task = Task::create([
            'code' => 'TSK-STITCH',
            'name' => 'Stitching Stage',
            'sequence_number' => 2,
            'status' => true,
        ]);

        $product = ManufacturingProduct::create([
            'name' => 'Standard Pillowcase',
            'title' => 'Standard Pillowcase',
            'code' => 'MP-PIL-001',
            'product_code' => 'MP-PIL-001',
            'status' => true,
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-002',
            'production_batch_id' => $batch->batch_code,
            'production_batch_db_id' => $batch->id,
            'task_id' => $task->id,
            'manufacturing_product_id' => $product->id,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'status' => 'in_progress',
        ]);

        $labor = Labor::create([
            'code' => 'LAB-001',
            'name' => 'John Doe',
            'status' => true,
            'is_active' => true,
        ]);

        $showResponse = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/production/jobs/{$job->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        // Test alias routes /api/v1/production/jobs/{id} and /api/v1/factory/jobs/{id}
        $prodShowResponse = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/production/jobs/{$job->id}");
        $prodShowResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $factoryShowResponse = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/jobs/{$job->id}");
        $factoryShowResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $assignResponse = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/production/jobs/{$job->id}/assign-laborers", [
                'labor_allocations' => [
                    [
                        'labor_id' => $labor->id,
                        'rate_per_piece' => 15.50,
                        'assigned_quantity' => 100,
                    ],
                ],
                'notes' => 'Assigned for stitching task',
            ]);

        $assignResponse->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_can_access_job_options_via_all_api_aliases(): void
    {
        // Test /api/v1/admin/production/jobs/options
        $adminOptionsResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/options');
        $adminOptionsResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tasks', 'manufacturing_products', 'laborers', 'statuses']]);

        // Test /api/v1/production/jobs/options
        $prodOptionsResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/production/jobs/options');
        $prodOptionsResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tasks', 'manufacturing_products', 'laborers', 'statuses']]);

        // Test /api/v1/factory/jobs/options
        $factoryOptionsResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/jobs/options');
        $factoryOptionsResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tasks', 'manufacturing_products', 'laborers', 'statuses']]);
    }
}
