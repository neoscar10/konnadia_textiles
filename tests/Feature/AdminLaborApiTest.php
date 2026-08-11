<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobLaborAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLaborApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Task $cuttingTask;
    protected Task $stitchingTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->cuttingTask = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-001',
            'is_labor_required' => true,
            'status' => true,
        ]);

        $this->stitchingTask = Task::create([
            'name' => 'Stitching',
            'code' => 'TSK-002',
            'is_labor_required' => true,
            'status' => true,
        ]);
    }

    /** @test */
    public function guest_cannot_access_labor_api()
    {
        $response = $this->getJson('/api/v1/admin/labor');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_admin_can_list_labors()
    {
        $labor = Labor::create([
            'name' => 'Ramesh Kumar',
            'mobile_number' => '9876543210',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 18000.00,
        ]);
        $labor->tasks()->sync([$this->cuttingTask->id]);

        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/labor');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('data.0.name', 'Ramesh Kumar')
            ->assertJsonPath('data.0.payment_method', 'monthly_salary');
    }

    /** @test */
    public function can_filter_and_search_labors()
    {
        $l1 = Labor::create([
            'name' => 'Suresh Verma',
            'code' => 'LAB-0001',
            'mobile_number' => '9998887771',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 20000.00,
        ]);
        $l1->tasks()->sync([$this->cuttingTask->id]);

        $l2 = Labor::create([
            'name' => 'Mahesh Singh',
            'code' => 'LAB-0002',
            'mobile_number' => '9998887772',
            'status' => false,
            'payment_method' => 'job_work',
        ]);
        $l2->tasks()->sync([$this->stitchingTask->id]);

        // Search by name
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/labor?search=Suresh');
        $response->assertStatus(200)->assertJsonCount(1, 'data');

        // Filter by payment method
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/labor?payment_method=job_work');
        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Mahesh Singh');

        // Filter by status
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/labor?status=inactive');
        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Mahesh Singh');

        // Filter by task_id
        $response = $this->actingAs($this->admin, 'api')->getJson("/api/v1/admin/labor?task_id={$this->cuttingTask->id}");
        $response->assertStatus(200)->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Suresh Verma');
    }

    /** @test */
    public function can_get_labor_options()
    {
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/labor/options');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'tasks',
                    'payment_methods',
                    'stats',
                ]
            ]);
    }

    /** @test */
    public function can_create_labor_with_authorized_tasks()
    {
        $payload = [
            'name' => 'Vikram Patel',
            'mobile_number' => '9123456789',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 22000.00,
            'authorized_tasks' => [$this->cuttingTask->id, $this->stitchingTask->id],
        ];

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/v1/admin/labor', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Vikram Patel',
                    'payment_method' => 'monthly_salary',
                ]
            ]);

        $this->assertDatabaseHas('labors', [
            'name' => 'Vikram Patel',
            'payment_method' => 'monthly_salary',
        ]);
    }

    /** @test */
    public function can_update_labor_details_and_tasks()
    {
        $labor = Labor::create([
            'name' => 'Old Name',
            'mobile_number' => '9000000000',
            'status' => true,
            'payment_method' => 'job_work',
        ]);
        $labor->tasks()->sync([$this->cuttingTask->id]);

        $payload = [
            'name' => 'Updated Name',
            'mobile_number' => '9111111111',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 25000.00,
            'authorized_tasks' => [$this->stitchingTask->id],
        ];

        $response = $this->actingAs($this->admin, 'api')->putJson("/api/v1/admin/labor/{$labor->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.payment_method', 'monthly_salary');

        $this->assertDatabaseHas('labors', [
            'id' => $labor->id,
            'name' => 'Updated Name',
            'payment_method' => 'monthly_salary',
        ]);
    }

    /** @test */
    public function can_toggle_labor_status()
    {
        $labor = Labor::create([
            'name' => 'Active Worker',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        $response = $this->actingAs($this->admin, 'api')->patchJson("/api/v1/admin/labor/{$labor->id}/toggle-status");

        $response->assertStatus(200)->assertJsonPath('data.status', false);
        $this->assertFalse($labor->fresh()->status);
    }

    /** @test */
    public function cannot_delete_labor_linked_to_allocations()
    {
        $labor = Labor::create([
            'name' => 'Worker With Allocations',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        $cat = \App\Models\ManufacturingProductCategory::create(['name' => 'Apparel', 'status' => true]);
        $prod = \App\Models\ManufacturingProduct::create(['name' => 'Shirt SKU', 'code' => 'MP-001', 'manufacturing_product_category_id' => $cat->id, 'status' => 'active']);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-001',
            'status' => 'scheduled',
            'manufacturing_product_id' => $prod->id,
            'planned_quantity' => 10,
            'supervisor_id' => $this->admin->id,
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-001',
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $prod->id,
            'target_quantity' => 10,
            'status' => 'in_progress',
        ]);

        JobLaborAllocation::create([
            'production_job_id' => $job->id,
            'task_id' => $this->cuttingTask->id,
            'labor_id' => $labor->id,
            'piece_rate' => 15.00,
            'assigned_quantity' => 10,
            'completed_quantity' => 10,
            'quantity_processed' => 10,
            'calculated_wage' => 150.00,
        ]);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/v1/admin/labor/{$labor->id}");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('labors', ['id' => $labor->id]);
    }

    /** @test */
    public function can_delete_labor_without_allocations()
    {
        $labor = Labor::create([
            'name' => 'Unlinked Worker',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        $response = $this->actingAs($this->admin, 'api')->deleteJson("/api/v1/admin/labor/{$labor->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('labors', ['id' => $labor->id]);
    }
}
