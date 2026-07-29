<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected Category $parentFolder;
    protected Category $leafCategory;
    protected CustomerLevel $level;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permCategories = Permission::firstOrCreate(['name' => 'access categories', 'guard_name' => 'web']);
        $permCustomers = Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permCategories, $permCustomers]);

        $this->superAdmin = User::factory()->create(['email' => 'super_cat@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_cat@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->level = CustomerLevel::create([
            'name' => 'Wholesale Gold',
            'discount_percentage' => 15.0,
            'default_credit_limit' => 50000,
            'is_active' => true,
        ]);

        $this->parentFolder = Category::create([
            'name' => 'Fabrics',
            'title' => 'Fabrics',
            'slug' => 'fabrics',
            'sort_order' => 1,
            'is_active' => true,
            'is_leaf' => false,
        ]);

        $this->leafCategory = Category::create([
            'name' => 'Silk Fabrics',
            'title' => 'Silk Fabrics',
            'slug' => 'silk-fabrics',
            'parent_id' => $this->parentFolder->id,
            'sort_order' => 1,
            'is_active' => true,
            'is_leaf' => true,
        ]);
    }

    public function test_guest_cannot_access_admin_category_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/categories');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_category_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/categories');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_category_tree_and_folder_contents(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/categories/tree');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => [['id', 'name', 'children']]]);

        $folderResponse = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/categories?parent_id=' . $this->parentFolder->id);

        $folderResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.current_category.id', $this->parentFolder->id)
            ->assertJsonCount(1, 'data.categories');
    }

    public function test_super_admin_can_create_category_folder_and_leaf(): void
    {
        $payload = [
            'name' => 'Cotton Fabrics',
            'parent_id' => $this->parentFolder->id,
            'description' => 'Pure cotton fabric range',
            'is_active' => true,
            'is_leaf' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/categories', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cotton Fabrics')
            ->assertJsonPath('data.is_leaf', true);

        $this->assertDatabaseHas('categories', [
            'name' => 'Cotton Fabrics',
            'parent_id' => $this->parentFolder->id,
        ]);
    }

    public function test_super_admin_can_update_category_and_toggle_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson('/api/v1/admin/categories/' . $this->leafCategory->id, [
                'name' => 'Pure Silk Fabrics',
                'description' => 'Updated silk description',
                'is_active' => true,
                'is_leaf' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Pure Silk Fabrics');

        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/admin/categories/' . $this->leafCategory->id . '/toggle-status');

        $toggleResp->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_super_admin_can_configure_and_fetch_category_defaults(): void
    {
        $defaultsPayload = [
            'base_price' => 1200.0,
            'description' => 'Default silk description',
            'hsn_code' => '5007',
            'gst_percentage' => 12.0,
            'minimum_order_quantity' => 10,
            'product_type' => 'retail',
            'units' => [
                'level1_name' => 'Meter',
                'level1_code' => 'm',
                'level2_name' => 'Roll',
                'level2_code' => 'roll',
                'level2_conversion' => 100.0,
            ],
            'pricingOverrides' => [
                $this->level->id => 15.0,
            ]
        ];

        $saveResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/categories/' . $this->leafCategory->id . '/defaults', $defaultsPayload);

        $saveResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $getResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/categories/' . $this->leafCategory->id . '/defaults');

        $getResp->assertStatus(200)
            ->assertJsonPath('data.defaults.hsn_code', '5007');
    }

    public function test_super_admin_can_move_products_and_delete_category(): void
    {
        $targetLeaf = Category::create([
            'name' => 'Satin Silk Fabrics',
            'title' => 'Satin Silk Fabrics',
            'slug' => 'satin-silk-fabrics',
            'parent_id' => $this->parentFolder->id,
            'is_leaf' => true,
        ]);

        $product = Product::create([
            'title' => 'Test Silk Roll',
            'sku' => 'KT-SILK-001',
            'base_price' => 1500.0,
            'description' => 'Test product in leaf category',
            'is_active' => true,
            'product_type' => 'retail',
        ]);
        $product->categories()->attach($this->leafCategory->id);

        // Move products
        $moveResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/categories/' . $this->leafCategory->id . '/move-products', [
                'target_category_id' => $targetLeaf->id,
            ]);

        $moveResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($targetLeaf->products()->where('products.id', $product->id)->exists());

        // Delete empty leaf category
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/categories/' . $this->leafCategory->id);

        $deleteResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', ['id' => $this->leafCategory->id]);
    }
}
