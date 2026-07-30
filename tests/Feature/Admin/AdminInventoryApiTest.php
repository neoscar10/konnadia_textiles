<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCombination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminInventoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected Product $product;
    protected ProductCombination $combination;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permInventory = Permission::firstOrCreate(['name' => 'access inventory', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permInventory]);

        $this->superAdmin = User::factory()->create(['email' => 'super_inv@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_inv@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->product = Product::create([
            'title' => 'Linen Trousers',
            'sku' => 'KT-TROUSER-01',
            'base_price' => 1500.00,
            'description' => 'Comfortable linen trousers',
            'stock_quantity' => 20,
            'is_active' => true,
            'product_type' => 'retail',
        ]);

        $this->combination = ProductCombination::create([
            'product_id' => $this->product->id,
            'sku' => 'KT-TROUSER-01-L-BLUE',
            'combination_values' => ['Size' => 'L', 'Color' => 'Blue'],
            'stock_quantity' => 15,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_inventory_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/inventory/stats');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_inventory_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/inventory/stats');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_inventory_stats_and_listing(): void
    {
        $statsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/inventory/stats');

        $statsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_items', 15);

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/inventory?search=Trousers');

        $listResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Linen Trousers');
    }

    public function test_super_admin_can_adjust_single_variant_stock(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/inventory/adjust', [
                'product_id' => $this->product->id,
                'combination_id' => $this->combination->id,
                'adjustment_type' => 'add',
                'quantity' => 10,
                'reason' => 'Restock shipment',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.new_stock_quantity', 25)
            ->assertJsonPath('data.product_total_stock', 25);

        $this->assertDatabaseHas('product_combinations', [
            'id' => $this->combination->id,
            'stock_quantity' => 25,
        ]);
    }

    public function test_super_admin_can_bulk_update_variant_stocks(): void
    {
        $comb2 = ProductCombination::create([
            'product_id' => $this->product->id,
            'sku' => 'KT-TROUSER-01-M-BLACK',
            'combination_values' => ['Size' => 'M', 'Color' => 'Black'],
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $url = '/api/v1/admin/inventory/products/' . $this->product->id . '/variants-stock';
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson($url, [
                'variant_stocks' => [
                    ['combination_id' => $this->combination->id, 'stock_quantity' => 30],
                    ['combination_id' => $comb2->id, 'stock_quantity' => 20],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_stock', 50);

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock_quantity' => 50,
        ]);
    }
}
