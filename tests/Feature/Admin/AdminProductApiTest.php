<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Tag;
use App\Models\Product;
use App\Models\Task;
use App\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected Category $category;
    protected CustomerLevel $level;
    protected Tag $tag;
    protected Task $task1;
    protected Task $task2;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permProducts = Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'web']);
        $permCustomers = Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permProducts, $permCustomers]);

        $this->superAdmin = User::factory()->create(['email' => 'super@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->category = Category::create([
            'name' => 'Shirts',
            'title' => 'Shirts',
            'slug' => 'shirts',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->level = CustomerLevel::create([
            'name' => 'Wholesale Gold',
            'discount_percentage' => 15.0,
            'default_credit_limit' => 50000,
            'is_active' => true,
        ]);

        $this->tag = Tag::create([
            'name' => 'Cotton',
            'slug' => 'cotton',
        ]);

        $this->task1 = Task::create([
            'name' => 'Cutting',
            'code' => 'CUT-01',
            'status' => true,
        ]);

        $this->task2 = Task::create([
            'name' => 'Stitching',
            'code' => 'STITCH-01',
            'status' => true,
        ]);
    }

    public function test_guest_cannot_access_admin_product_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/products');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_product_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/products');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_product_options_and_reference_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/products/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'categories', 'customer_levels', 'tags', 'product_types', 'default_units'
                ]
            ]);
    }

    public function test_super_admin_can_create_product(): void
    {
        $payload = [
            'title' => 'Premium Oxford Cotton Shirt',
            'sku' => 'KT-OXF-001',
            'base_price' => 799.00,
            'description' => 'High quality formal oxford shirt.',
            'hsn_code' => '6205',
            'gst_percentage' => 12.0,
            'minimum_order_quantity' => 5,
            'product_type' => 'retail',
            'is_active' => true,
            'stock_quantity' => 100,
            'category_ids' => [$this->category->id],
            'tag_ids' => [$this->tag->id],
            'units' => [
                'level1_name' => 'Piece',
                'level1_code' => 'pcs',
                'level2_name' => 'Box',
                'level2_code' => 'box',
                'level2_conversion' => 10,
            ],
            'customer_level_prices' => [
                [
                    'customer_level_id' => $this->level->id,
                    'discount_percentage' => 20.0,
                ]
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Premium Oxford Cotton Shirt')
            ->assertJsonPath('data.sku', 'KT-OXF-001');

        $this->assertDatabaseHas('products', [
            'title' => 'Premium Oxford Cotton Shirt',
            'sku' => 'KT-OXF-001',
        ]);
    }

    public function test_super_admin_can_update_product_and_toggle_status(): void
    {
        $product = Product::create([
            'title' => 'Original Shirt',
            'sku' => 'KT-SHIRT-ORIG',
            'base_price' => 500.0,
            'description' => 'Original description',
            'is_active' => true,
            'product_type' => 'retail',
        ]);
        $product->categories()->attach($this->category->id);

        // Update product
        $updatePayload = [
            'title' => 'Updated Shirt Title',
            'base_price' => 650.0,
            'description' => 'Updated description content',
            'product_type' => 'retail',
            'category_ids' => [$this->category->id],
        ];

        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson("/api/v1/admin/products/{$product->id}", $updatePayload);

        $updateResp->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Shirt Title')
            ->assertJsonPath('data.base_price', 650);

        // Toggle status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson("/api/v1/admin/products/{$product->id}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse((bool) $product->fresh()->is_active);
    }

    public function test_super_admin_can_upload_and_manage_media(): void
    {
        $product = Product::create([
            'title' => 'Media Test Shirt',
            'sku' => 'KT-MEDIA-001',
            'base_price' => 300.0,
            'description' => 'Media testing',
            'is_active' => true,
            'product_type' => 'retail',
        ]);

        $file1 = UploadedFile::fake()->image('shirt1.jpg', 600, 600);
        $file2 = UploadedFile::fake()->image('shirt2.jpg', 600, 600);

        $uploadResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson("/api/v1/admin/products/{$product->id}/media", [
                'images' => [$file1, $file2]
            ]);

        $uploadResp->assertStatus(200)
            ->assertJsonPath('success', true);

        $mediaItems = ProductMedia::where('product_id', $product->id)->get();
        $this->assertCount(2, $mediaItems);

        $secondMedia = $mediaItems->where('is_primary', false)->first();
        $this->assertNotNull($secondMedia);

        // Set second media as primary
        $primaryResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson("/api/v1/admin/products/{$product->id}/media/{$secondMedia->id}/primary");

        $primaryResp->assertStatus(200);
        $this->assertTrue((bool) $secondMedia->fresh()->is_primary);

        // Delete first media
        $firstMedia = $mediaItems->where('is_primary', true)->first();
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson("/api/v1/admin/products/{$product->id}/media/{$firstMedia->id}");

        $deleteResp->assertStatus(200);
        $this->assertDatabaseMissing('product_media', ['id' => $firstMedia->id]);
    }

    public function test_super_admin_can_delete_product(): void
    {
        $product = Product::create([
            'title' => 'Product to Delete',
            'sku' => 'KT-DEL-001',
            'base_price' => 100.0,
            'description' => 'To be deleted',
            'is_active' => true,
            'product_type' => 'retail',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
