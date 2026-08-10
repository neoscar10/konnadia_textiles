<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Models\RawMaterialCategory;
use App\Models\ManufacturingProduct;
use Spatie\Permission\Models\Role;

class AdminTaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->category = RawMaterialCategory::create([
            'name' => 'Fabric Category',
            'code' => 'CAT-FAB-TEST',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_task_api()
    {
        $response = $this->getJson('/api/v1/admin/tasks');
        $response->assertStatus(401);
    }

    public function test_authenticated_admin_can_list_tasks()
    {
        Task::create(['name' => 'Cutting', 'code' => 'TSK-0001', 'sequence_number' => 1, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);
        Task::create(['name' => 'Stitching', 'code' => 'TSK-0002', 'sequence_number' => 2, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/tasks');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_and_search_tasks()
    {
        Task::create(['name' => 'Cutting Process', 'code' => 'TSK-0001', 'sequence_number' => 1, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);
        Task::create(['name' => 'Embroidery', 'code' => 'TSK-0002', 'sequence_number' => 2, 'status' => false, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/tasks?search=Cutting');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Cutting Process');
    }

    public function test_can_create_task_with_raw_material_categories()
    {
        $payload = [
            'name' => 'Fabric Cutting',
            'code' => 'TSK-CUT-01',
            'status' => true,
            'consumes_raw_material' => true,
            'is_labor_required' => true,
            'selected_category_ids' => [$this->category->id],
            'sequence_number' => 1,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/tasks', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Fabric Cutting')
            ->assertJsonPath('data.consumes_raw_material', true);

        $task = Task::where('code', 'TSK-CUT-01')->first();
        $this->assertNotNull($task);
        $this->assertCount(1, $task->rawMaterialCategories);
    }

    public function test_can_update_task()
    {
        $task = Task::create(['name' => 'Old Name', 'code' => 'TSK-0005', 'sequence_number' => 5, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/tasks/{$task->id}", [
                'name' => 'Updated Task Name',
                'consumes_raw_material' => false,
                'is_labor_required' => true,
                'sequence_number' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Task Name');

        $this->assertEquals('Updated Task Name', $task->fresh()->name);
    }

    public function test_can_toggle_task_status()
    {
        $task = Task::create(['name' => 'Testing Toggle', 'code' => 'TSK-0009', 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/tasks/{$task->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', false);

        $this->assertFalse((bool) $task->fresh()->status);
    }

    public function test_can_reorder_tasks()
    {
        $t1 = Task::create(['name' => 'Task A', 'code' => 'TSK-A', 'sequence_number' => 1, 'consumes_raw_material' => false, 'is_labor_required' => true]);
        $t2 = Task::create(['name' => 'Task B', 'code' => 'TSK-B', 'sequence_number' => 2, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/tasks/reorder', [
                'ordered_ids' => [$t2->id, $t1->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(1, $t2->fresh()->sequence_number);
        $this->assertEquals(2, $t1->fresh()->sequence_number);
    }

    public function test_cannot_delete_task_linked_to_manufacturing_products()
    {
        $task = Task::create(['name' => 'Locked Task', 'code' => 'TSK-LOCK', 'consumes_raw_material' => false, 'is_labor_required' => true]);
        $mProd = ManufacturingProduct::create([
            'name' => 'Test Product',
            'code' => 'MP-TEST-001',
            'unit' => 'Pcs',
            'is_active' => true,
        ]);
        $mProd->tasks()->attach($task->id, ['sequence_number' => 1, 'standard_labor_rate' => 10.00]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/tasks/{$task->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }
}
