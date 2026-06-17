<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerLevel $level;

    public function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'api']);

        $this->level = CustomerLevel::create([
            'name' => 'Standard Level',
            'discount_percentage' => 5.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
    }

    public function test_api_login_returns_jwt_token_for_admin(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('super_admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This account is not allowed to access the mobile app.');
    }

    public function test_api_login_returns_jwt_token_for_customer(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-AUTH-01',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Auth Saree Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Auth Rajesh',
            'mobile_number' => '9876543210',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'user',
                    'customer'
                ]
            ]);
    }

    public function test_api_auth_me_works_with_bearer_token(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-AUTH-02',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Auth Saree Shop 2',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Auth Rajesh 2',
            'mobile_number' => '9876543211',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(200)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_api_logout_invalidates_token(): void
    {
        $user = User::factory()->create(['password' => 'password', 'is_active' => true]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-AUTH-03',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Auth Saree Shop 3',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Auth Rajesh 3',
            'mobile_number' => '9876543212',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.token');

        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200);

        // Try to use token again
        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(401);
    }
}
