<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRawMaterialApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $category;

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

        $this->category = RawMaterialCategory::create([
            'name' => 'Fabric Category',
            'code' => 'CAT-FAB-TEST',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_list_and_filter_raw_materials()
    {
        RawMaterial::create([
            'name' => 'Cotton Roll 90 Inch',
            'code' => 'RM-FAB-0001',
            'raw_material_category_id' => $this->category->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/factory/raw-materials?search=Cotton');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.code', 'RM-FAB-0001');
    }

    public function test_super_admin_can_fetch_raw_material_options()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/factory/raw-materials/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.categories.0.name', 'Fabric Category');
    }

    public function test_super_admin_can_create_and_update_raw_material()
    {
        $createPayload = [
            'raw_material_category_id' => $this->category->id,
            'name' => 'Silk Thread Red',
            'code' => 'RM-STITCH-99',
            'unit' => 'Spool',
            'is_active' => true,
        ];

        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/factory/raw-materials', $createPayload);

        $createResponse->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silk Thread Red');

        $materialId = $createResponse->json('data.id');

        $updateResponse = $this->actingAs($this->admin, 'api')
            ->putJson('/api/v1/admin/factory/raw-materials/' . $materialId, [
                'raw_material_category_id' => $this->category->id,
                'name' => 'Silk Thread Dark Red',
                'unit' => 'Spool',
                'is_active' => true,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silk Thread Dark Red');
    }

    public function test_cannot_delete_raw_material_linked_to_inventory_batches()
    {
        $material = RawMaterial::create([
            'name' => 'Thread Roll',
            'code' => 'RM-LOCKED-01',
            'raw_material_category_id' => $this->category->id,
            'unit' => 'Spool',
            'is_active' => true,
        ]);

        InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Test Supplier',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-001',
            'quantity_received' => 10,
            'balance_quantity' => 10,
            'unit' => 'Spool',
            'purchase_rate' => 100,
            'total_amount' => 1000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/admin/factory/raw-materials/' . $material->id);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('linked_batches_count', 1);
    }
}
