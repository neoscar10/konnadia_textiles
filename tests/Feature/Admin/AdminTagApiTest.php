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

class AdminTagApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected Category $parentCategory;
    protected Category $childCategory;
    protected Tag $tag;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permTags = Permission::firstOrCreate(['name' => 'access tags', 'guard_name' => 'web']);
        $permCustomers = Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permTags, $permCustomers]);

        $this->superAdmin = User::factory()->create(['email' => 'super_tag@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_tag@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->parentCategory = Category::create([
            'name' => 'Apparel',
            'title' => 'Apparel',
            'slug' => 'apparel',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->childCategory = Category::create([
            'name' => 'Formal Shirts',
            'title' => 'Formal Shirts',
            'slug' => 'formal-shirts',
            'parent_id' => $this->parentCategory->id,
            'sort_order' => 1,
            'is_active' => true,
            'is_leaf' => true,
        ]);

        $this->tag = Tag::create([
            'name' => 'Pure Linen',
            'slug' => 'pure-linen',
        ]);
        $this->tag->categories()->attach($this->childCategory->id);
    }

    public function test_guest_cannot_access_admin_tag_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/tags');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_tag_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/tags');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_tag_options_and_listing(): void
    {
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/tags/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['category_tree', 'leaf_categories']]);

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/tags?search=Linen');

        $listResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pure Linen');
    }

    public function test_super_admin_can_create_tag_with_categories(): void
    {
        $payload = [
            'name' => 'Organic Cotton',
            'category_ids' => [$this->parentCategory->id],
            'include_descendants' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/tags', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Organic Cotton')
            ->assertJsonPath('data.slug', 'organic-cotton');

        $this->assertDatabaseHas('tags', [
            'name' => 'Organic Cotton',
            'slug' => 'organic-cotton',
        ]);
    }

    public function test_super_admin_can_update_tag(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson('/api/v1/admin/tags/' . $this->tag->id, [
                'name' => 'Premium Pure Linen',
                'category_ids' => [$this->childCategory->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Premium Pure Linen')
            ->assertJsonPath('data.slug', 'premium-pure-linen');
    }

    public function test_super_admin_can_delete_tag(): void
    {
        $product = Product::create([
            'title' => 'Linen Shirt',
            'sku' => 'KT-LINEN-01',
            'base_price' => 999.00,
            'description' => 'Test shirt',
            'is_active' => true,
            'product_type' => 'retail',
        ]);
        $product->tags()->attach($this->tag->id);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/tags/' . $this->tag->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tags', ['id' => $this->tag->id]);
        $this->assertDatabaseMissing('product_tag', ['tag_id' => $this->tag->id]);
    }
}
