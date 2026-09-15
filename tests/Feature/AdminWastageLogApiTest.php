<?php

namespace Tests\Feature;

use App\Models\JobWastage;
use App\Models\ManufacturingProduct;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWastageLogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Task $defaultTask;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access production');

        // Create a shared default task (task_id is NOT NULL in job_wastages)
        $this->defaultTask = Task::create(['name' => 'Default Stage', 'code' => 'TSK-DEFAULT', 'status' => true]);
    }

    /** Create a ManufacturingProduct with a unique code */
    private function makeProduct(string $code, string $name = 'Test Product'): ManufacturingProduct
    {
        return ManufacturingProduct::create(['name' => $name, 'code' => $code, 'status' => 'active']);
    }

    /** Create a JobWastage with required fields always present */
    private function makeWastage(array $overrides = []): JobWastage
    {
        static $counter = 0;
        $counter++;
        return JobWastage::create(array_merge([
            'job_code'     => "WST-TEST-{$counter}",
            'task_id'      => $this->defaultTask->id,
            'wastage_type' => 'scrap',
            'quantity_wasted' => 2.0,
        ], $overrides));
    }

    // ─── Auth & Permission Guards ─────────────────────────────────────────────

    public function test_guest_cannot_access_wastage_log_index(): void
    {
        $this->getJson('/api/v1/factory/wastage-log')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_access_stats(): void
    {
        $this->getJson('/api/v1/factory/wastage-log/stats')->assertStatus(401);
    }

    public function test_admin_without_production_permission_is_denied(): void
    {
        $restricted = User::factory()->create(['is_active' => true]);
        $restricted->assignRole('admin');

        $this->actingAs($restricted, 'api')
            ->getJson('/api/v1/factory/wastage-log')
            ->assertStatus(403);
    }

    // ─── Stats Endpoint ───────────────────────────────────────────────────────

    public function test_stats_endpoint_returns_kpi_data(): void
    {
        $this->makeWastage(['wastage_type' => 'scrap',   'quantity_wasted' => 5.00]);
        $this->makeWastage(['wastage_type' => 'damaged', 'quantity_wasted' => 3.00]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.loss_incidents_count', 2)
            ->assertJson(['data' => ['total_wastage_qty' => 8]]);
    }

    // ─── Options Endpoint ─────────────────────────────────────────────────────

    public function test_options_endpoint_returns_tasks_and_wastage_types(): void
    {
        // default task is already created; add one more
        Task::create(['name' => 'Stitching', 'code' => 'TSK-STITCH', 'status' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['tasks', 'wastage_types', 'recent_jobs']])
            ->assertJsonCount(2, 'data.tasks')   // defaultTask + Stitching
            ->assertJsonCount(2, 'data.wastage_types');
    }

    // ─── Index with Filters ───────────────────────────────────────────────────

    public function test_index_returns_paginated_wastage_entries(): void
    {
        $this->makeWastage(['wastage_type' => 'scrap']);
        $this->makeWastage(['wastage_type' => 'damaged']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta', 'pagination'])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_wastage_type(): void
    {
        $this->makeWastage(['wastage_type' => 'scrap',   'quantity_wasted' => 4.0]);
        $this->makeWastage(['wastage_type' => 'damaged', 'quantity_wasted' => 2.0]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log?wastage_type=scrap');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.wastage_type', 'scrap');
    }

    public function test_index_filters_by_task(): void
    {
        $task2 = Task::create(['name' => 'Ironing', 'code' => 'T2', 'status' => true]);

        $this->makeWastage(['task_id' => $this->defaultTask->id, 'quantity_wasted' => 3.0]);
        $this->makeWastage(['task_id' => $task2->id,            'quantity_wasted' => 1.0]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/wastage-log?task_id={$this->defaultTask->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.task.name', $this->defaultTask->name);
    }

    public function test_index_search_works_by_reason(): void
    {
        $this->makeWastage(['reason' => 'Edge tear defect']);
        $this->makeWastage(['reason' => 'Normal scrap batch']);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log?search=Edge+tear');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    // ─── Show Endpoint ────────────────────────────────────────────────────────

    public function test_show_returns_single_wastage_entry_with_correct_fields(): void
    {
        $wastage = $this->makeWastage([
            'wastage_type'    => 'scrap',
            'quantity_wasted' => 5.0,
            'reason'          => 'Packing damage',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/wastage-log/{$wastage->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $wastage->id)
            ->assertJsonPath('data.wastage_type', 'scrap')
            ->assertJsonPath('data.reason', 'Packing damage')
            ->assertJsonStructure(['data' => [
                'wastage_code', 'wastage_type_label', 'formatted_quantity_wasted', 'stage_lost', 'logged_date',
            ]]);
    }

    public function test_show_returns_404_for_missing_entry(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/wastage-log/99999')
            ->assertStatus(404);
    }

    // ─── Store Endpoint ───────────────────────────────────────────────────────

    public function test_can_create_wastage_log_entry(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/wastage-log', [
                'wastage_type'    => 'scrap',
                'quantity_wasted' => 7.5,
                'reason'          => 'Test scrap from sewing',
                'task_id'         => $this->defaultTask->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.wastage_type', 'scrap')
            ->assertJsonPath('data.quantity_wasted', 7.5);

        $this->assertDatabaseHas('job_wastages', ['wastage_type' => 'scrap', 'quantity_wasted' => '7.50']);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/wastage-log', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wastage_type', 'quantity_wasted']);
    }

    public function test_store_validates_wastage_type_enum(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/wastage-log', ['wastage_type' => 'broken', 'quantity_wasted' => 2.0])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wastage_type']);
    }

    // ─── Update Endpoint ──────────────────────────────────────────────────────

    public function test_can_update_wastage_log_entry(): void
    {
        $wastage = $this->makeWastage(['wastage_type' => 'scrap', 'quantity_wasted' => 3.0]);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/factory/wastage-log/{$wastage->id}", [
                'quantity_wasted' => 6.0,
                'reason'          => 'Updated reason',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(6.0, (float) $response->json('data.quantity_wasted'));

        $this->assertDatabaseHas('job_wastages', ['id' => $wastage->id, 'quantity_wasted' => '6.00', 'reason' => 'Updated reason']);
    }

    public function test_update_rejects_invalid_wastage_type(): void
    {
        $wastage = $this->makeWastage();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/factory/wastage-log/{$wastage->id}", ['wastage_type' => 'invalid_type'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['wastage_type']);
    }

    // ─── Destroy Endpoint ─────────────────────────────────────────────────────

    public function test_can_delete_wastage_log_entry(): void
    {
        $wastage = $this->makeWastage();

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/factory/wastage-log/{$wastage->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('job_wastages', ['id' => $wastage->id]);
    }

    public function test_delete_returns_404_for_missing_entry(): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/factory/wastage-log/99999')
            ->assertStatus(404);
    }

    // ─── Admin Production Namespace ────────────────────────────────────────────

    public function test_admin_production_namespace_also_works(): void
    {
        $this->makeWastage(['quantity_wasted' => 1.0]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/wastage-log')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
