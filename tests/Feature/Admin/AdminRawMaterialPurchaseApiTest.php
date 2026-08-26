<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRawMaterialPurchaseApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $fabricCategory;
    protected RawMaterial $fabricMaterial;

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

        $this->fabricCategory = RawMaterialCategory::create([
            'name' => 'Fabric Material',
            'code' => 'CAT-FAB',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);

        $this->fabricMaterial = RawMaterial::create([
            'name' => 'Premium Satin Silk',
            'code' => 'RM-SATIN-01',
            'raw_material_category_id' => $this->fabricCategory->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);
    }

    public function test_can_fetch_purchase_options()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/factory/raw-materials/purchase/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.raw_materials.0.name', 'Premium Satin Silk');
    }

    public function test_can_record_purchase_entry_and_create_batch_with_unopened_bales()
    {
        $payload = [
            'supplier_name' => 'Textile Mill Co',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-MILL-101',
            'raw_material_id' => $this->fabricMaterial->id,
            'num_bales' => 2,
            'declared_bale_length' => 100,
            'all_bales_equal_length' => true,
            'purchase_rate' => 50,
            'gst_included' => false,
            'gst_percent' => 18,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/factory/raw-materials/purchase', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity_received', 200)
            ->assertJsonPath('data.num_bales', 2);

        $batchId = $response->json('data.id');
        $batch = InventoryBatch::with('bales')->find($batchId);

        $this->assertNotNull($batch);
        $this->assertEquals(2, $batch->bales->count());
        $this->assertEquals(100, $batch->bales->first()->declared_length);
        $this->assertFalse((bool)$batch->bales->first()->is_opened);
    }
}
