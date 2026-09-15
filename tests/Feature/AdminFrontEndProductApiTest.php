<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FrontEndProduct;
use App\Models\FrontEndProductComponent;
use App\Models\FrontEndProductPackaging;
use App\Models\ManufacturingProduct;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminFrontEndProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $leafCategory;
    protected ManufacturingProduct $mfgProduct;

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

        // Create a manufactured leaf category
        $this->leafCategory = Category::create([
            'name'                  => 'Bed Sheets',
            'slug'                  => 'bed-sheets',
            'is_leaf'               => true,
            'is_active'             => true,
            'default_product_config'=> ['product_type' => 'manufactured'],
        ]);

        // Create a manufacturing product to use in components
        $this->mfgProduct = ManufacturingProduct::create([
            'name'   => 'Bed Sheet 180TC',
            'code'   => 'MP-BED-001',
            'status' => 'active',
        ]);
    }

    // ─── Auth & Permission Guards ──────────────────────────────────────────────

    public function test_guest_cannot_access_front_end_products(): void
    {
        $this->getJson('/api/v1/factory/front-end-products')->assertStatus(401);
    }

    public function test_admin_without_production_permission_is_denied(): void
    {
        $restricted = User::factory()->create(['is_active' => true]);
        $restricted->assignRole('admin');

        $this->actingAs($restricted, 'api')
            ->getJson('/api/v1/factory/front-end-products')
            ->assertStatus(403);
    }

    // ─── Stats Endpoint ────────────────────────────────────────────────────────

    public function test_stats_returns_correct_kpi_data(): void
    {
        // Create a configured FEP
        $fep = FrontEndProduct::create([
            'name'             => 'Bed Sheets',
            'sku'              => 'CAT-CFG-0001',
            'category_id'      => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'        => true,
        ]);
        FrontEndProductComponent::create([
            'front_end_product_id'    => $fep->id,
            'manufacturing_product_id'=> $this->mfgProduct->id,
            'quantity'                => 1,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.configured_count', 1)
            ->assertJsonPath('data.total_leaf_categories', 1);
    }

    // ─── Options Endpoint ──────────────────────────────────────────────────────

    public function test_options_returns_manufacturing_products_and_packaging_materials(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['manufacturing_products', 'packaging_materials', 'leaf_categories'],
            ])
            ->assertJsonCount(1, 'data.manufacturing_products')
            ->assertJsonCount(1, 'data.leaf_categories');
    }

    // ─── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_all_leaf_categories_with_config_status(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta', 'pagination'])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_name', 'Bed Sheets')
            ->assertJsonPath('data.0.is_configured', false);
    }

    public function test_index_configured_category_shows_is_configured_true(): void
    {
        $fep = FrontEndProduct::create([
            'name'             => 'Bed Sheets',
            'sku'              => 'CAT-CFG-0001',
            'category_id'      => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'        => true,
        ]);
        FrontEndProductComponent::create([
            'front_end_product_id'    => $fep->id,
            'manufacturing_product_id'=> $this->mfgProduct->id,
            'quantity'                => 2,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.is_configured', true)
            ->assertJsonPath('data.0.front_end_product.is_active', true);
    }

    public function test_index_search_filters_by_category_name(): void
    {
        // Add a second leaf category
        Category::create([
            'name'                  => 'Pillow Cases',
            'slug'                  => 'pillow-cases',
            'is_leaf'               => true,
            'is_active'             => true,
            'default_product_config'=> ['product_type' => 'manufactured'],
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products?search=Bed');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category_name', 'Bed Sheets');
    }

    public function test_index_filter_by_configured_status(): void
    {
        // Create second unconfigured leaf category
        Category::create([
            'name'                  => 'Towels',
            'slug'                  => 'towels',
            'is_leaf'               => true,
            'is_active'             => true,
            'default_product_config'=> ['product_type' => 'manufactured'],
        ]);

        // Configure only the first
        $fep = FrontEndProduct::create([
            'name'             => 'Bed Sheets',
            'sku'              => 'CAT-CFG-0001',
            'category_id'      => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'        => true,
        ]);
        FrontEndProductComponent::create([
            'front_end_product_id'    => $fep->id,
            'manufacturing_product_id'=> $this->mfgProduct->id,
            'quantity'                => 1,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products?status=configured');

        $response->assertStatus(200)->assertJsonCount(1, 'data');

        $response2 = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products?status=unconfigured');

        $response2->assertStatus(200)->assertJsonCount(1, 'data');
    }

    // ─── Show Endpoint ─────────────────────────────────────────────────────────

    public function test_show_returns_category_config(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.category_id', $this->leafCategory->id)
            ->assertJsonPath('data.category_name', 'Bed Sheets')
            ->assertJsonPath('data.is_configured', false);
    }

    public function test_show_returns_404_for_invalid_category(): void
    {
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/front-end-products/99999')
            ->assertStatus(404);
    }

    // ─── Configure Endpoint ────────────────────────────────────────────────────

    public function test_can_create_category_configuration(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'components' => [
                    ['manufacturing_product_id' => $this->mfgProduct->id, 'quantity' => 2],
                ],
                'packaging_items' => [],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_configured', true)
            ->assertJsonPath('data.category_name', 'Bed Sheets');

        $this->assertDatabaseHas('front_end_products', [
            'category_id' => $this->leafCategory->id,
            'sku'         => 'CAT-CFG-' . str_pad($this->leafCategory->id, 4, '0', STR_PAD_LEFT),
        ]);

        $this->assertDatabaseHas('front_end_product_components', [
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity'                 => 2,
        ]);
    }

    public function test_configure_updates_existing_configuration(): void
    {
        // First configure
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'components' => [['manufacturing_product_id' => $this->mfgProduct->id, 'quantity' => 1]],
            ]);

        $mfgProduct2 = ManufacturingProduct::create(['name' => 'Pillow Insert', 'code' => 'MP-PIL-001', 'status' => 'active']);

        // Re-configure with updated components
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'components' => [['manufacturing_product_id' => $mfgProduct2->id, 'quantity' => 3]],
            ]);

        $response->assertStatus(200)->assertJsonPath('success', true);

        // Old component should be gone
        $this->assertDatabaseMissing('front_end_product_components', [
            'manufacturing_product_id' => $this->mfgProduct->id,
        ]);

        // New component should exist
        $this->assertDatabaseHas('front_end_product_components', [
            'manufacturing_product_id' => $mfgProduct2->id,
            'quantity'                 => 3,
        ]);
    }

    public function test_configure_with_packaging_items(): void
    {
        $category = \App\Models\RawMaterialCategory::create([
            'name'      => 'Packaging',
            'code'      => 'CAT-PKG',
            'unit_type' => 'other',
            'is_active' => true,
        ]);

        $rawMaterial = RawMaterial::create([
            'name'                    => 'Polybag',
            'code'                    => 'RM-PKG-001',
            'unit'                    => 'Pcs',
            'is_active'               => true,
            'raw_material_category_id'=> $category->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'components' => [
                    ['manufacturing_product_id' => $this->mfgProduct->id, 'quantity' => 1],
                ],
                'packaging_items' => [
                    ['raw_material_id' => $rawMaterial->id, 'quantity' => 2],
                ],
            ]);

        $response->assertStatus(201)->assertJsonPath('success', true);

        $this->assertDatabaseHas('front_end_product_packagings', [
            'raw_material_id' => $rawMaterial->id,
            'quantity'        => 2,
        ]);
    }

    public function test_configure_validates_required_components(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'packaging_items' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['components']);
    }

    public function test_configure_validates_component_manufacturing_product_exists(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/factory/front-end-products/{$this->leafCategory->id}/configure", [
                'components' => [
                    ['manufacturing_product_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['components.0.manufacturing_product_id']);
    }

    public function test_configure_returns_404_for_invalid_category(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/front-end-products/99999/configure', [
                'components' => [['manufacturing_product_id' => $this->mfgProduct->id, 'quantity' => 1]],
            ])
            ->assertStatus(404);
    }

    // ─── Toggle Status ─────────────────────────────────────────────────────────

    public function test_can_toggle_status_of_front_end_product(): void
    {
        $fep = FrontEndProduct::create([
            'name'             => 'Bed Sheets',
            'sku'              => 'CAT-CFG-0001',
            'category_id'      => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/factory/front-end-products/{$fep->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('front_end_products', ['id' => $fep->id, 'is_active' => false]);
    }

    // ─── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_delete_front_end_product_without_batches(): void
    {
        $fep = FrontEndProduct::create([
            'name'             => 'Bed Sheets',
            'sku'              => 'CAT-CFG-0001',
            'category_id'      => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/factory/front-end-products/{$fep->id}");

        $response->assertStatus(200)->assertJsonPath('success', true);
        $this->assertSoftDeleted('front_end_products', ['id' => $fep->id]);
    }

    public function test_delete_returns_404_for_missing_config(): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/v1/factory/front-end-products/99999')
            ->assertStatus(404);
    }

    // ─── Admin Production Namespace ────────────────────────────────────────────

    public function test_admin_production_namespace_also_works(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/front-end-products');

        $response->assertStatus(200)->assertJsonPath('success', true);
    }
}
