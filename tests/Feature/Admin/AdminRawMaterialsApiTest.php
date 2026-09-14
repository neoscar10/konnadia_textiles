<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\Supplier;
use App\Models\FabricWidth;
use App\Models\InventoryBatch;
use App\Models\UnitGroup;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminRawMaterialsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permProduction = Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        $superRole->givePermissionTo([$permProduction]);

        $this->superAdmin = User::factory()->create(['email' => 'super_rawmat@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->token = JWTAuth::fromUser($this->superAdmin);
    }

    /** @test */
    public function test_raw_material_categories_api_crud_and_status_toggle(): void
    {
        // 1. Create Category
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/raw-material-categories', [
                'name' => 'Premium Cotton Fabrics',
                'code' => 'CAT-COTTON',
                'unit_type' => 'length_based',
                'description' => 'Cotton rolls and weaves',
                'status' => true,
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Premium Cotton Fabrics');

        $catId = $createResp->json('data.id');

        // 2. Index Listing
        $indexResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/raw-material-categories?search=Cotton');

        $indexResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 3. Show Details
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/admin/production/raw-material-categories/{$catId}");

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'CAT-COTTON');

        // 4. Update
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/admin/production/raw-material-categories/{$catId}", [
                'name' => 'Super Premium Cotton Fabrics',
                'unit_type' => 'length_based',
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Super Premium Cotton Fabrics');

        // 5. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/raw-material-categories/{$catId}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', false);

        // 6. Delete
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/raw-material-categories/{$catId}");

        $deleteResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('raw_material_categories', ['id' => $catId]);
    }

    /** @test */
    public function test_raw_material_category_deletion_protection_when_linked(): void
    {
        $cat = RawMaterialCategory::create([
            'name' => 'Linked Category',
            'code' => 'CAT-LINK',
            'unit_type' => 'other',
            'status' => true,
        ]);

        RawMaterial::create([
            'name' => 'Linked Material',
            'code' => 'RM-LINK-01',
            'raw_material_category_id' => $cat->id,
            'is_active' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/raw-material-categories/{$cat->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('linked_raw_materials_count', 1);

        $this->assertDatabaseHas('raw_material_categories', ['id' => $cat->id]);
    }

    /** @test */
    public function test_suppliers_api_crud_and_options(): void
    {
        // 1. Create Supplier
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/suppliers', [
                'name' => 'Silk & Spun Traders',
                'contact_person' => 'Rajesh Sharma',
                'whatsapp_number' => '+919876543210',
                'email' => 'rajesh@silkspun.com',
                'gstin' => '07AAAAA0000A1Z5',
                'address' => 'Delhi Textile Market',
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silk & Spun Traders');

        $supplierId = $createResp->json('data.id');

        // 2. Options Lookup
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/suppliers/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Show Details
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/admin/production/suppliers/{$supplierId}");

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.contact_person', 'Rajesh Sharma');

        // 4. Update Supplier
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/admin/production/suppliers/{$supplierId}", [
                'name' => 'Silk & Spun Traders Private Limited',
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Silk & Spun Traders Private Limited');

        // 5. Delete Supplier
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/suppliers/{$supplierId}");

        $deleteResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('suppliers', ['id' => $supplierId]);
    }

    /** @test */
    public function test_fabric_width_master_api_crud_status_toggle_and_usage(): void
    {
        // 1. Create Fabric Width
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/fabric-widths', [
                'name' => 'Extra Wide Rolls',
                'value' => 72.00,
                'unit' => 'IN',
                'status' => true,
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.value', 72);

        $widthId = $createResp->json('data.id');

        // 2. Options Lookup
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/fabric-widths/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/fabric-widths/{$widthId}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', false);

        // 4. Delete Fabric Width
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/fabric-widths/{$widthId}");

        $deleteResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fabric_widths', ['id' => $widthId]);
    }

    /** @test */
    public function test_raw_material_catalog_api_options_crud_and_status_toggle(): void
    {
        $cat = RawMaterialCategory::create([
            'name' => 'Denim Category',
            'code' => 'CAT-DENIM',
            'unit_type' => 'length_based',
            'status' => true,
        ]);

        // 1. Options Lookup
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/raw-materials/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['code_preview', 'categories', 'fabric_widths', 'suppliers']]);

        // 2. Create Raw Material
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/raw-materials', [
                'name' => '14oz Heavy Denim',
                'code' => 'RM-DENIM-14',
                'raw_material_category_id' => $cat->id,
                'unit' => 'Meters',
                'standard_width' => 60,
                'width_unit' => 'IN',
                'is_active' => true,
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', '14oz Heavy Denim');

        $matId = $createResp->json('data.id');

        // 3. Index Listing
        $indexResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/raw-materials?search=Denim');

        $indexResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 4. Update Raw Material
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/admin/production/raw-materials/{$matId}", [
                'name' => '14oz Heavy Raw Denim',
                'raw_material_category_id' => $cat->id,
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', '14oz Heavy Raw Denim');

        // 5. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/raw-materials/{$matId}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        // 6. Delete Raw Material
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/raw-materials/{$matId}");

        $deleteResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('raw_materials', ['id' => $matId]);
    }

    /** @test */
    public function test_raw_material_purchase_entry_api_length_and_unit_based_creation(): void
    {
        $cat = RawMaterialCategory::create([
            'name' => 'Pure Linen Category',
            'code' => 'CAT-LINEN',
            'unit_type' => 'length_based',
            'status' => true,
        ]);

        $material = RawMaterial::create([
            'name' => 'Pure Soft Linen Fabric',
            'code' => 'RM-LINEN-01',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'standard_width' => 58,
            'width_unit' => 'IN',
            'is_active' => true,
        ]);

        $supplier = Supplier::create([
            'name' => 'Linen Craft Mills',
            'contact_person' => 'Sanjay Mehta',
        ]);

        // Purchase options
        $optsResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/raw-material-purchases/options');

        $optsResp->assertStatus(200)
            ->assertJsonPath('success', true);

        // Store length-based purchase entry with bales
        $purchaseResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/raw-material-purchases', [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->format('Y-m-d'),
                'invoice_number' => 'INV-LINEN-2026-001',
                'raw_material_id' => $material->id,
                'purchase_rate' => 250.00,
                'num_bales' => 2,
                'all_bales_equal_length' => true,
                'declared_bale_length' => 100,
                'gst_included' => true,
            ]);

        $purchaseResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.raw_material_name', 'Pure Soft Linen Fabric')
            ->assertJsonPath('data.supplier_name', 'Linen Craft Mills')
            ->assertJsonPath('data.quantity_received', 200);

        $batchId = $purchaseResp->json('data.id');

        $this->assertDatabaseHas('inventory_batches', [
            'id' => $batchId,
            'num_bales' => 2,
            'balance_quantity' => 200,
        ]);
    }

    /** @test */
    public function test_inventory_batches_api_listing_detail_open_bale_and_stock_adjustments(): void
    {
        $cat = RawMaterialCategory::create([
            'name' => 'Silk Fabrics',
            'code' => 'CAT-SILK',
            'unit_type' => 'length_based',
            'status' => true,
        ]);

        $material = RawMaterial::create([
            'name' => 'Banarasi Silk Roll',
            'code' => 'RM-SILK-01',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        $batch = InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Silk Emporium',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-SILK-99',
            'quantity_received' => 100,
            'balance_quantity' => 100,
            'base_quantity' => 100,
            'base_current_balance' => 100,
            'purchase_rate' => 500.00,
            'total_amount' => 50000.00,
            'unit' => 'Meters',
            'num_bales' => 1,
            'declared_bale_length' => 100,
            'status' => 'active',
        ]);
        $batch->createBales(1, 100);

        $bale = $batch->bales()->first();
        $this->assertNotNull($bale);

        // 1. Batches Index
        $indexResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/inventory-batches?search=Silk');

        $indexResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 2. Batch Show Detail
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/admin/production/inventory-batches/{$batch->id}");

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.batch_number', $batch->batch_number);

        // 3. Open Bale into measured rolls
        $openBaleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/admin/production/inventory-batches/{$batch->id}/bales/{$bale->id}/open", [
                'bale_roll_count' => 2,
                'bale_roll_lengths' => [
                    ['length' => 50.5, 'design_number' => 'DES-001', 'stock_id' => 'STK-001'],
                    ['length' => 49.5, 'design_number' => 'DES-002', 'stock_id' => 'STK-002'],
                ],
            ]);

        $openBaleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.total_recorded_length', 100);

        // 4. Stock Adjustment
        $adjustResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/v1/admin/production/inventory-batches/{$batch->id}/adjust-quantity", [
                'quantity' => 10,
                'adjustment_type' => 'deduct',
                'reason' => 'Quality sample cut',
            ]);

        $adjustResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance_quantity', 90);
    }

    /** @test */
    public function test_factory_alias_endpoints_work(): void
    {
        $aliasCatResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/raw-material-categories');
        $aliasCatResp->assertStatus(200)->assertJsonPath('success', true);

        $aliasSupResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/suppliers');
        $aliasSupResp->assertStatus(200)->assertJsonPath('success', true);

        $aliasFWResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/fabric-widths');
        $aliasFWResp->assertStatus(200)->assertJsonPath('success', true);

        $aliasRMResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/raw-materials');
        $aliasRMResp->assertStatus(200)->assertJsonPath('success', true);
    }
}
