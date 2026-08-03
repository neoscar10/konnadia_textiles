<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\HomeContentSection;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminHomeContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'access home-content', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access home-content', 'guard_name' => 'api']);
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo('access home-content');

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->token = auth('api')->login($this->admin);
    }

    public function test_can_fetch_home_content_stats(): void
    {
        HomeContentSection::create(['type' => 'banner', 'title' => 'Active Banner', 'is_active' => true, 'sort_order' => 1]);
        HomeContentSection::create(['type' => 'banner', 'title' => 'Inactive Banner', 'is_active' => false, 'sort_order' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/admin/home-content/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_sections',
                    'active_sections',
                    'inactive_sections',
                    'scheduled_sections',
                ]
            ]);
    }

    public function test_can_list_home_content_sections(): void
    {
        HomeContentSection::create(['type' => 'banner', 'title' => 'Section 1', 'is_active' => true, 'sort_order' => 1]);
        HomeContentSection::create(['type' => 'category_slider', 'title' => 'Section 2', 'is_active' => true, 'sort_order' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/admin/home-content');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'is_active',
                        'items_count',
                    ]
                ],
                'pagination',
            ]);
    }

    public function test_can_fetch_options_metadata(): void
    {
        Category::create(['name' => 'Test Category', 'title' => 'Test Category', 'slug' => 'test-category', 'is_active' => true]);
        Product::create(['title' => 'Test Product', 'slug' => 'test-product', 'sku' => 'PROD-001', 'base_price' => 100, 'stock_quantity' => 10, 'is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/admin/home-content/options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'section_types',
                    'categories',
                    'products',
                ]
            ]);
    }

    public function test_can_create_banner_section_via_api(): void
    {
        Storage::fake('public');

        $payload = [
            'type' => 'banner',
            'title' => 'Festive Sale Banner',
            'subtitle' => 'Exclusive textile discounts',
            'is_active' => true,
            'items' => [
                [
                    'item_type' => 'banner',
                    'cta_label' => 'Shop Now',
                    'link_type' => 'url',
                    'external_url' => 'https://example.com/sale',
                    'image' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
                ]
            ]
        ];

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/home-content', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Festive Sale Banner');

        $this->assertDatabaseHas('home_content_sections', [
            'title' => 'Festive Sale Banner',
            'type' => 'banner',
        ]);
    }

    public function test_can_upload_standalone_media(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('slide.jpg', 800, 600);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/home-content/upload-media', [
                'file' => $file,
                'folder' => 'slides',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['image_path', 'image_url']
            ]);
    }

    public function test_can_reorder_sections(): void
    {
        $s1 = HomeContentSection::create(['type' => 'banner', 'title' => 'S1', 'sort_order' => 1]);
        $s2 = HomeContentSection::create(['type' => 'banner', 'title' => 'S2', 'sort_order' => 2]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/home-content/reorder', [
                'ordered_ids' => [$s2->id, $s1->id]
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $s2->fresh()->sort_order);
        $this->assertEquals(1, $s1->fresh()->sort_order);
    }

    public function test_can_toggle_section_status(): void
    {
        $s = HomeContentSection::create(['type' => 'banner', 'title' => 'S', 'is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/admin/home-content/{$s->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        $this->assertFalse($s->fresh()->is_active);
    }

    public function test_can_delete_section(): void
    {
        $s = HomeContentSection::create(['type' => 'banner', 'title' => 'ToBeDeletedSection']);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/admin/home-content/{$s->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('home_content_sections', ['id' => $s->id]);
    }
}
