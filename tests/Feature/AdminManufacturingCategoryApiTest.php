<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminManufacturingCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin = User::factory()->create([
            'email' => 'api_mfg_admin@konnadia.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');

        $this->token = JWTAuth::fromUser($this->admin);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_api_can_list_manufacturing_product_categories(): void
    {
        ManufacturingProductCategory::create(['name' => 'Bedsheets', 'status' => true]);
        ManufacturingProductCategory::create(['name' => 'Pillow Covers', 'status' => true]);

        $response = $this->getJson('/api/v1/admin/production/product-categories', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'status', 'status_label', 'manufacturing_products_count']
                ]
            ]);
    }

    public function test_api_can_get_options_for_pickers(): void
    {
        ManufacturingProductCategory::create(['name' => 'Active Category', 'status' => true]);
        ManufacturingProductCategory::create(['name' => 'Inactive Category', 'status' => false]);

        $response = $this->getJson('/api/v1/admin/production/product-categories/options', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Active Category');
    }

    public function test_api_can_create_manufacturing_product_category(): void
    {
        $payload = [
            'name' => 'Curtains & Drapes',
            'status' => true,
        ];

        $response = $this->postJson('/api/v1/admin/production/product-categories', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Curtains & Drapes')
            ->assertJsonPath('data.status', true);

        $this->assertDatabaseHas('manufacturing_product_categories', [
            'name' => 'Curtains & Drapes',
        ]);
    }

    public function test_api_validates_duplicate_category_name(): void
    {
        ManufacturingProductCategory::create(['name' => 'Existing Category', 'status' => true]);

        $payload = [
            'name' => 'Existing Category',
        ];

        $response = $this->postJson('/api/v1/admin/production/product-categories', $payload, $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_api_can_show_and_update_manufacturing_product_category(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Table Runners', 'status' => true]);

        // Show
        $showRes = $this->getJson("/api/v1/admin/production/product-categories/{$cat->id}", $this->authHeaders());
        $showRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Table Runners');

        // Update
        $updateRes = $this->putJson("/api/v1/admin/production/product-categories/{$cat->id}", [
            'name' => 'Luxury Table Runners',
        ], $this->authHeaders());

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Luxury Table Runners');
    }

    public function test_api_can_toggle_category_status(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Toggle Cat', 'status' => true]);

        $response = $this->patchJson("/api/v1/admin/production/product-categories/{$cat->id}/toggle-status", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.status', false)
            ->assertJsonPath('data.status_label', 'Inactive');
    }

    public function test_api_can_delete_empty_category(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Empty Cat', 'status' => true]);

        $response = $this->deleteJson("/api/v1/admin/production/product-categories/{$cat->id}", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('manufacturing_product_categories', ['id' => $cat->id]);
    }

    public function test_api_prevents_deleting_category_linked_to_manufacturing_products(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Linked Cat', 'status' => true]);

        ManufacturingProduct::create([
            'manufacturing_product_category_id' => $cat->id,
            'name' => 'Linked Sheet Product',
            'code' => 'MP-LINK-001',
            'standard_labor_rate' => 15.00,
            'status' => 'active',
        ]);

        $response = $this->deleteJson("/api/v1/admin/production/product-categories/{$cat->id}", [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('linked_products_count', 1);
    }
}
