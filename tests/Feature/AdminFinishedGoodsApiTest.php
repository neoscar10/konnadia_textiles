<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Models\FrontEndProductComponent;
use App\Models\InventoryBatch;
use App\Models\ManufacturingProduct;
use App\Models\Product;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFinishedGoodsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $leafCategory;
    protected ManufacturingProduct $mfgProduct;
    protected FrontEndProduct $feProduct;

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

        // Manufactured leaf category
        $this->leafCategory = Category::create([
            'name'                   => 'Bed Sheets',
            'slug'                   => 'bed-sheets',
            'is_leaf'                => true,
            'is_active'              => true,
            'default_product_config' => ['product_type' => 'manufactured'],
        ]);

        // Manufacturing product
        $this->mfgProduct = ManufacturingProduct::create([
            'name'   => 'KTC Bed Sheet 180TC',
            'code'   => 'MP-BED-001',
            'status' => 'active',
        ]);

        // FrontEndProduct (assembly config)
        $this->feProduct = FrontEndProduct::create([
            'name'               => 'Bed Sheets',
            'sku'                => 'CAT-CFG-' . str_pad($this->leafCategory->id, 4, '0', STR_PAD_LEFT),
            'category_id'        => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'          => true,
        ]);

        FrontEndProductComponent::create([
            'front_end_product_id'     => $this->feProduct->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity'                 => 1,
        ]);
    }

    // ─── Auth & Permission Guards ──────────────────────────────────────────────

    public function test_guest_cannot_access_finished_goods(): void
    {
        $this->getJson('/api/v1/factory/finished-goods')->assertStatus(401);
    }

    public function test_admin_without_production_permission_is_denied(): void
    {
        $restricted = User::factory()->create(['is_active' => true]);
        $restricted->assignRole('admin');

        $this->actingAs($restricted, 'api')
            ->getJson('/api/v1/factory/finished-goods')
            ->assertStatus(403);
    }

    // ─── Stats ─────────────────────────────────────────────────────────────────

    public function test_stats_returns_kpi_data(): void
    {
        FinishedGoodsBatch::create([
            'barcode'             => 'FG-TEST-2026-0010',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'TEST',
            'converted_qty'       => 10,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_batches', 1)
            ->assertJsonPath('data.total_converted_qty', 10)
            ->assertJsonStructure([
                'data' => [
                    'total_batches',
                    'total_converted_qty',
                    'total_unique_designs',
                    'total_categories_converted',
                    'recent_batches_7_days',
                    'recent_qty_7_days',
                    'configured_categories_count',
                ],
            ]);
    }

    // ─── Options ───────────────────────────────────────────────────────────────

    public function test_options_returns_leaf_categories(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['leaf_categories', 'configured_category_ids', 'storefront_products'],
            ])
            ->assertJsonCount(1, 'data.leaf_categories')
            ->assertJsonPath('data.leaf_categories.0.is_configured', true);
    }

    public function test_options_returns_storefront_products_for_category(): void
    {
        $product = Product::create([
            'title'          => 'KTC Bed Sheet 180TC',
            'sku'            => 'KT-P-001',
            'is_active'      => true,
            'stock_quantity' => 5,
            'base_price'     => 100.00,
        ]);
        $product->categories()->attach($this->leafCategory->id);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/finished-goods/options?category_id={$this->leafCategory->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.storefront_products');
    }

    // ─── Stock Check ───────────────────────────────────────────────────────────

    public function test_stock_check_returns_can_proceed_for_available_stock(): void
    {
        // Create a completed job with available stock
        ProductionJob::create([
            'job_code'                 => 'JOB-001',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity'          => 50,
            'converted_quantity'       => 0,
            'status'                   => 'completed',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/stock-check', [
                'category_id' => $this->leafCategory->id,
                'target_qty'  => 10,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('can_proceed', true)
            ->assertJsonStructure(['can_proceed', 'mfg_stock', 'pkg_stock', 'missing_items', 'category_config']);
    }

    public function test_stock_check_returns_cannot_proceed_for_unconfigured_category(): void
    {
        $unconfiguredCat = Category::create([
            'name'                   => 'Unconfigured Category',
            'slug'                   => 'unconfigured',
            'is_leaf'                => true,
            'is_active'              => true,
            'default_product_config' => ['product_type' => 'manufactured'],
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/stock-check', [
                'category_id' => $unconfiguredCat->id,
                'target_qty'  => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('can_proceed', false);
    }

    public function test_stock_check_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/stock-check', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'target_qty']);
    }

    // ─── Barcode Search ────────────────────────────────────────────────────────

    public function test_barcode_search_finds_batch_by_barcode(): void
    {
        $batch = FinishedGoodsBatch::create([
            'barcode'             => 'FG-DSG108-2026-0010',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'DSG108',
            'converted_qty'       => 10,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/barcode-search?q=FG-DSG108');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $batch->id)
            ->assertJsonPath('data.barcode', 'FG-DSG108-2026-0010');
    }

    public function test_barcode_search_returns_404_for_no_match(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/barcode-search?q=FG-NONEXISTENT')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_barcode_search_requires_q_param(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/barcode-search')
            ->assertStatus(422);
    }

    // ─── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_batches(): void
    {
        FinishedGoodsBatch::create([
            'barcode'             => 'FG-DSG001-2026-0010',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'DSG001',
            'converted_qty'       => 10,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure(['data', 'meta', 'pagination'])
            ->assertJsonPath('data.0.barcode', 'FG-DSG001-2026-0010');
    }

    public function test_index_search_filters_by_barcode(): void
    {
        FinishedGoodsBatch::create([
            'barcode'             => 'FG-BEDSHEET-2026-0001',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'BEDSHEET',
            'converted_qty'       => 5,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        FinishedGoodsBatch::create([
            'barcode'             => 'FG-TOWEL-2026-0001',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'TOWEL',
            'converted_qty'       => 3,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods?search=BEDSHEET');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.design_id', 'BEDSHEET');
    }

    public function test_index_empty_returns_success(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    // ─── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_full_audit_detail(): void
    {
        $batch = FinishedGoodsBatch::create([
            'barcode'             => 'FG-AUDIT-2026-0005',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'AUDIT',
            'converted_qty'       => 5,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
            'costing_summary'     => ['totalUnitCost' => '₹362.00'],
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/finished-goods/{$batch->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $batch->id)
            ->assertJsonPath('data.barcode', 'FG-AUDIT-2026-0005')
            ->assertJsonStructure(['data' => ['id', 'barcode', 'design_id', 'items', 'packaging_deductions', 'costing_summary']]);
    }

    public function test_show_returns_404_for_missing_batch(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/finished-goods/99999')
            ->assertStatus(404);
    }

    // ─── Convert ───────────────────────────────────────────────────────────────

    public function test_can_convert_category_to_finished_goods(): void
    {
        // Create completed production job stock
        ProductionJob::create([
            'job_code'                 => 'JOB-002',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity'          => 100,
            'converted_quantity'       => 0,
            'status'                   => 'completed',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [
                'category_id' => $this->leafCategory->id,
                'target_qty'  => 10,
                'design_type' => 'new',
                'design_id'   => 'DSG108GOLD',
                'notes'       => 'Test conversion',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.converted_qty', 10)
            ->assertJsonPath('data.design_id', 'DSG108GOLD');

        $this->assertDatabaseHas('finished_goods_batches', [
            'converted_qty' => 10,
            'design_id'     => 'DSG108GOLD',
        ]);
    }

    public function test_convert_validates_required_fields(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'target_qty', 'design_type']);
    }

    public function test_convert_validates_design_id_required_for_new_design(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [
                'category_id' => $this->leafCategory->id,
                'target_qty'  => 5,
                'design_type' => 'new',
                // No design_id
            ])
            ->assertStatus(422);
    }

    public function test_convert_rejects_unconfigured_category(): void
    {
        $unconfiguredCat = Category::create([
            'name'                   => 'Unconfigured',
            'slug'                   => 'unconfigured',
            'is_leaf'                => true,
            'is_active'              => true,
            'default_product_config' => ['product_type' => 'manufactured'],
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [
                'category_id' => $unconfiguredCat->id,
                'target_qty'  => 5,
                'design_type' => 'new',
                'design_id'   => 'DSG001',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_convert_with_existing_design_requires_product_id(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [
                'category_id' => $this->leafCategory->id,
                'target_qty'  => 5,
                'design_type' => 'existing',
                // No existing_storefront_product_id
            ])
            ->assertStatus(422);
    }

    public function test_convert_with_existing_product(): void
    {
        ProductionJob::create([
            'job_code'                 => 'JOB-003',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity'          => 50,
            'converted_quantity'       => 0,
            'status'                   => 'completed',
        ]);

        $product = Product::create([
            'title'          => 'Existing Bed Sheet',
            'sku'            => 'KT-P-EXIST',
            'is_active'      => true,
            'stock_quantity' => 0,
            'base_price'     => 100.00,
        ]);
        $product->categories()->attach($this->leafCategory->id);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/finished-goods/convert', [
                'category_id'                    => $this->leafCategory->id,
                'target_qty'                     => 5,
                'design_type'                    => 'existing',
                'existing_storefront_product_id' => $product->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.converted_qty', 5);
    }

    // ─── Toggle Publish ────────────────────────────────────────────────────────

    public function test_can_toggle_publish_status(): void
    {
        $batch = FinishedGoodsBatch::create([
            'barcode'             => 'FG-PUB-2026-0001',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'PUB',
            'converted_qty'       => 5,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/factory/finished-goods/{$batch->id}/toggle-publish");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_published', false);

        $this->assertDatabaseHas('finished_goods_batches', ['id' => $batch->id, 'is_published' => false]);
    }

    // ─── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_soft_delete_finished_goods_batch(): void
    {
        $batch = FinishedGoodsBatch::create([
            'barcode'             => 'FG-DEL-2026-0001',
            'front_end_product_id'=> $this->feProduct->id,
            'design_id'           => 'DEL',
            'converted_qty'       => 1,
            'is_published'        => true,
            'converted_date'      => now(),
            'created_by'          => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/factory/finished-goods/{$batch->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('finished_goods_batches', ['id' => $batch->id]);
    }

    public function test_delete_returns_404_for_missing_batch(): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/factory/finished-goods/99999')
            ->assertStatus(404);
    }

    // ─── Admin Namespace Alias ─────────────────────────────────────────────────

    public function test_admin_production_namespace_works(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/finished-goods')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
