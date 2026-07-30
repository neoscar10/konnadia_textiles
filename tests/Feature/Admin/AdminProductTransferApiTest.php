<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\RetailShop;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductCombination;
use App\Models\ProductTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminProductTransferApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected RetailShop $shop;
    protected Product $product;
    protected ProductUnit $unit;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permTransfers = Permission::firstOrCreate(['name' => 'access product-transfers', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permTransfers]);

        $this->superAdmin = User::factory()->create(['email' => 'super_trans@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_trans@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->shop = RetailShop::create([
            'name' => 'Surat Central Shop',
            'shop_code' => 'RSH-20001',
            'address' => '50 Ring Road',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395003',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'title' => 'Silk Saree Set',
            'sku' => 'KT-SAREE-SET-01',
            'base_price' => 5000.00,
            'description' => 'Fine silk saree set',
            'stock_quantity' => 100,
            'is_active' => true,
            'product_type' => 'retail',
        ]);

        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'level' => 1,
            'name' => 'Set',
            'short_code' => 'set',
            'conversion_to_base' => 1.0,
        ]);
    }

    public function test_guest_cannot_access_product_transfer_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/product-transfers');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_product_transfer_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/product-transfers');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_transfer_options_and_product_info(): void
    {
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/product-transfers/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['retail_shops', 'categories']]);

        $infoResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/product-transfers/products/' . $this->product->id . '/transfer-info');

        $infoResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.available_stock', 100);
    }

    public function test_super_admin_can_create_and_complete_product_transfer(): void
    {
        $payload = [
            'retail_shop_id' => $this->shop->id,
            'transfer_date' => now()->toDateString(),
            'notes' => 'Dispatching 10 saree sets to Surat Central Shop',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_unit_id' => $this->unit->id,
                    'quantity' => 10,
                    'note' => 'First batch',
                ]
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/product-transfers', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.total_base_quantity', 10);

        // Check stock was deducted
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'stock_quantity' => 90,
        ]);
    }
}
