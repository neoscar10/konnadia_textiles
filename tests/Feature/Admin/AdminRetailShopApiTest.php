<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\RetailShop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminRetailShopApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected RetailShop $shop;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permShops = Permission::firstOrCreate(['name' => 'access retail-shops', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permShops]);

        $this->superAdmin = User::factory()->create(['email' => 'super_shop@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_shop@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->shop = RetailShop::create([
            'name' => 'Konnadia Flagship Store',
            'shop_code' => 'RSH-10001',
            'address' => '123 Textile Market Road',
            'city' => 'Surat',
            'state' => 'Gujarat',
            'pincode' => '395002',
            'contact_person' => 'Rajesh Sharma',
            'contact_phone' => '+91 9876543210',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_retail_shop_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/retail-shops');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_retail_shop_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/retail-shops');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_retail_shops_listing_and_options(): void
    {
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/retail-shops/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/retail-shops?search=Flagship');

        $listResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Konnadia Flagship Store');
    }

    public function test_super_admin_can_create_retail_shop(): void
    {
        $payload = [
            'name' => 'Konnadia Retail Outlet 2',
            'address' => '45 MG Road',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'pincode' => '380001',
            'contact_person' => 'Amit Patel',
            'contact_phone' => '+91 9123456789',
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/retail-shops', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Konnadia Retail Outlet 2');

        $this->assertDatabaseHas('retail_shops', [
            'name' => 'Konnadia Retail Outlet 2',
            'city' => 'Ahmedabad',
        ]);
    }

    public function test_super_admin_can_update_and_toggle_retail_shop(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson('/api/v1/admin/retail-shops/' . $this->shop->id, [
                'name' => 'Konnadia Main Flagship Store',
                'address' => '123 Textile Market Road Updated',
                'is_active' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Konnadia Main Flagship Store');

        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/admin/retail-shops/' . $this->shop->id . '/toggle-status');

        $toggleResp->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_super_admin_can_delete_retail_shop(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/retail-shops/' . $this->shop->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('retail_shops', ['id' => $this->shop->id]);
    }
}
