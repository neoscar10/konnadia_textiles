<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryBatchApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected InventoryBatch $batch;

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

        $category = RawMaterialCategory::create([
            'name' => 'Fabric Material',
            'code' => 'CAT-FAB',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);

        $material = RawMaterial::create([
            'name' => 'Cotton Fabric 100% Pure',
            'code' => 'RM-COTTON-01',
            'raw_material_category_id' => $category->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        $this->batch = InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Apex Textiles',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-APEX-555',
            'quantity_received' => 200,
            'balance_quantity' => 200,
            'unit' => 'Meters',
            'purchase_rate' => 45,
            'total_amount' => 9000,
            'num_bales' => 2,
            'declared_bale_length' => 100,
            'status' => 'active',
        ]);

        $this->batch->createBales(2, 100);
    }

    public function test_can_list_inventory_batches_with_summary_kpis()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/factory/raw-materials/batches');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_batches', 1)
            ->assertJsonPath('summary.active_batches', 1)
            ->assertJsonPath('data.0.supplier_name', 'Apex Textiles');
    }

    public function test_can_show_inventory_batch_detail()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/factory/raw-materials/batches/' . $this->batch->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.batch_number', $this->batch->batch_number)
            ->assertJsonCount(2, 'data.bales');
    }

    public function test_can_open_bale_and_split_into_measured_rolls()
    {
        $bale = $this->batch->bales()->first();

        $payload = [
            'bale_roll_count' => 2,
            'bale_roll_lengths' => [50, 52], // Measured total 102 vs declared 100 (+2m length override)
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/factory/raw-materials/batches/{$this->batch->id}/bales/{$bale->id}/open", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.total_recorded_length', 102);

        $this->batch->refresh();
        $this->assertEquals(202, $this->batch->balance_quantity); // 200 + 2 extra measured meters
    }

    public function test_can_manually_adjust_batch_quantity()
    {
        $payload = [
            'adjustment_type' => 'deduct',
            'quantity' => 50,
            'reason' => 'Water damage in warehouse storage area C',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/factory/raw-materials/batches/{$this->batch->id}/adjust-quantity", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance_quantity', 150);
    }
}
