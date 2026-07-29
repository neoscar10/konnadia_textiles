<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminDesignCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected Category $category;
    protected Tag $tag;
    protected Product $product;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permDesign = Permission::firstOrCreate(['name' => 'access design-catalog', 'guard_name' => 'web']);
        $permCustomers = Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permDesign, $permCustomers]);

        $this->superAdmin = User::factory()->create(['email' => 'super_design@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_design@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->category = Category::create([
            'name' => 'Embroidery Fabrics',
            'title' => 'Embroidery Fabrics',
            'slug' => 'embroidery-fabrics',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->tag = Tag::create([
            'name' => 'Handcrafted',
            'slug' => 'handcrafted',
        ]);

        $this->product = Product::create([
            'title' => 'Royal Gold Embroidered Silk',
            'sku' => 'KT-EMB-001',
            'base_price' => 2499.00,
            'description' => 'Exquisite gold embroidered silk fabric design.',
            'is_active' => true,
            'product_type' => 'retail',
            'stock_quantity' => 25,
        ]);
        $this->product->categories()->attach($this->category->id);
        $this->product->tags()->attach($this->tag->id);
    }

    public function test_guest_cannot_access_design_catalog_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/design-catalog');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_design_catalog(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/design-catalog');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_design_catalog_options(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/design-catalog/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'leaf_categories',
                    'tags',
                ]
            ]);
    }

    public function test_super_admin_can_fetch_design_catalog_listing(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/design-catalog?search=Royal');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Royal Gold Embroidered Silk')
            ->assertJsonPath('data.0.stock_details.stock_status', 'in_stock');
    }

    public function test_super_admin_can_generate_shareable_catalog_url(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/design-catalog/share', [
                'search' => 'Royal',
                'category_id' => $this->category->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'share_url',
                    'share_text',
                    'applied_filters',
                ]
            ]);
    }
}
