<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Labor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionJobApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProduct $mfgProduct;
    protected ProductionBatch $batch;
    protected ProductionJob $job;
    protected Task $task;
    protected Labor $labor;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super_admin');
        $this->admin->givePermissionTo('access production');

        $this->task = Task::create([
            'code' => 'TSK-CUT-01',
            'name' => 'Cutting',
            'sequence_number' => 1,
            'consumes_raw_material' => true,
            'default_rate_per_piece' => 5.00,
            'status' => true,
        ]);

        $this->mfgProduct = ManufacturingProduct::create([
            'product_code' => 'MP-BED-001',
            'name' => 'King Bed Sheet',
            'title' => 'King Bed Sheet',
            'length' => 100,
            'width' => 90,
            'unit' => 'inch',
            'status' => true,
        ]);

        $this->mfgProduct->tasks()->attach($this->task->id, ['sequence_number' => 1]);

        $this->batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-9901',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'planned_quantity' => 100,
            'status' => 'In Progress',
            'supervisor_id' => $this->admin->id,
            'batch_date' => now()->format('Y-m-d'),
        ]);

        $this->job = ProductionJob::create([
            'job_code' => 'JOB-2026-9901',
            'production_batch_id' => $this->batch->batch_code,
            'production_batch_db_id' => $this->batch->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'task_id' => $this->task->id,
            'supervisor_id' => $this->admin->id,
            'target_quantity' => 100,
            'completed_quantity' => 0,
            'status' => 'in_progress',
        ]);

        $this->labor = Labor::create([
            'labor_code' => 'LBR-001',
            'name' => 'Ramesh Kumar',
            'phone' => '9876543210',
            'daily_rate' => 500,
            'piece_rate' => 5,
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_list_production_jobs_and_filter_by_status()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs?status=in_progress');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_jobs', 1)
            ->assertJsonPath('data.0.job_code', 'JOB-2026-9901');
    }

    public function test_super_admin_can_fetch_job_options()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tasks.0.code', 'TSK-CUT-01')
            ->assertJsonPath('data.laborers.0.labor_code', 'LAB-0001');
    }

    public function test_super_admin_can_view_job_detail()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/' . $this->job->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('job.job_code', 'JOB-2026-9901')
            ->assertJsonPath('job.manufacturing_product.title', 'King Bed Sheet');
    }

    public function test_super_admin_can_assign_laborers_to_job()
    {
        $payload = [
            'labor_allocations' => [
                [
                    'labor_id' => $this->labor->id,
                    'rate_per_piece' => 6.00,
                    'assigned_quantity' => 100,
                ]
            ],
            'notes' => 'Assigned cutting supervisor',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/production/jobs/' . $this->job->id . '/assign-laborers', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('job.labor_allocations.0.labor_id', $this->labor->id);
    }

    public function test_cannot_alter_product_to_larger_area_product()
    {
        $largerProduct = ManufacturingProduct::create([
            'product_code' => 'MP-SUPER-001',
            'name' => 'Super Jumbo Blanket',
            'title' => 'Super Jumbo Blanket',
            'length' => 200,
            'width' => 200,
            'unit' => 'inch',
            'status' => true,
        ]);

        $payload = [
            'target_manufacturing_product_id' => $largerProduct->id,
            'quantity' => 2,
            'reason' => 'Defective piece re-purpose',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/production/jobs/' . $this->job->id . '/record-alteration', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_manufacturing_product_id']);
    }
}
