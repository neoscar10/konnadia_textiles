<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use App\Models\LaborCategory;
use App\Models\RawMaterialCategory;
use App\Models\ManufacturingProduct;
use Spatie\Permission\Models\Role;

class AdminTaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $category;
    protected LaborCategory $laborCategory;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access production');

        $this->category = RawMaterialCategory::create([
            'name' => 'Fabric Category',
            'code' => 'CAT-FAB-TEST',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);

        $this->laborCategory = LaborCategory::create([
            'name' => 'Tailoring Experts',
            'code' => 'LCAT-0001',
            'status' => true,
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

    public function test_can_create_task_with_raw_material_categories_and_labor_category()
    {
        $payload = [
            'name' => 'Fabric Cutting',
            'code' => 'TSK-CUT-01',
            'status' => true,
            'consumes_raw_material' => true,
            'is_labor_required' => true,
            'labor_category_id' => $this->laborCategory->id,
            'selected_category_ids' => [$this->category->id],
            'sequence_number' => 1,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/tasks', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Fabric Cutting')
            ->assertJsonPath('data.consumes_raw_material', true)
            ->assertJsonPath('data.labor_category_id', $this->laborCategory->id)
            ->assertJsonPath('data.labor_category.name', 'Tailoring Experts');

        $task = Task::where('code', 'TSK-CUT-01')->first();
        $this->assertNotNull($task);
        $this->assertEquals($this->laborCategory->id, $task->labor_category_id);
        $this->assertCount(1, $task->rawMaterialCategories);
    }

    public function test_can_create_and_update_task_with_authorized_labor_tasks()
    {
        $lTask1 = Task::create(['name' => 'Labor Step A', 'code' => 'LTSK-01', 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $payload = [
            'name' => 'Assembly Task',
            'code' => 'TSK-ASSM-01',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => true,
            'labor_category_id' => $this->laborCategory->id,
            'selected_authorized_task_ids' => [$lTask1->id],
            'sequence_number' => 10,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/tasks', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.selected_authorized_task_ids.0', $lTask1->id);

        $optionsResp = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/tasks/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.all_labor_tasks')
            ->assertJsonCount(1, 'data.labor_categories')
            ->assertJsonPath('data.labor_categories.0.name', 'Tailoring Experts');
    }

    public function test_can_update_task()
    {
        $task = Task::create(['name' => 'Old Name', 'code' => 'TSK-0005', 'sequence_number' => 5, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/tasks/{$task->id}", [
                'name' => 'Updated Task Name',
                'consumes_raw_material' => false,
                'is_labor_required' => true,
                'labor_category_id' => $this->laborCategory->id,
                'sequence_number' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Task Name')
            ->assertJsonPath('data.labor_category_id', $this->laborCategory->id);

        $this->assertEquals('Updated Task Name', $task->fresh()->name);
        $this->assertEquals($this->laborCategory->id, $task->fresh()->labor_category_id);
    }

    public function test_clears_labor_category_when_labor_not_required()
    {
        $task = Task::create(['name' => 'Labor Task', 'code' => 'TSK-0010', 'sequence_number' => 10, 'status' => true, 'consumes_raw_material' => false, 'is_labor_required' => true, 'labor_category_id' => $this->laborCategory->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/admin/tasks/{$task->id}", [
                'name' => 'No Labor Task',
                'consumes_raw_material' => false,
                'is_labor_required' => false,
                'labor_category_id' => $this->laborCategory->id, // should be reset to null automatically
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_labor_required', false)
            ->assertJsonPath('data.labor_category_id', null);

        $this->assertNull($task->fresh()->labor_category_id);
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