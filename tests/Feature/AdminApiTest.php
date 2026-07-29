<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'api']);
    }

    public function test_super_admin_can_login_via_api(): void
    {
        $admin = User::factory()->create([
            'email' => 'superadmin@kanodia.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'login' => 'superadmin@kanodia.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'admin' => ['id', 'name', 'email', 'roles'],
                    'access_matrix' => ['is_super_admin'],
                ]
            ]);
    }

    public function test_regular_customer_is_blocked_from_admin_login(): void
    {
        $customerUser = User::factory()->create([
            'email' => 'customer@kanodia.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $customerUser->assignRole('customer');

        $response = $this->postJson('/api/v1/admin/auth/login', [
            'login' => 'customer@kanodia.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Access denied. Account does not have admin access permissions.',
            ]);
    }

    public function test_super_admin_can_create_and_manage_admin_via_api(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@kanodia.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        $token = auth('api')->login($superAdmin);

        // 1. Fetch available permissions
        $permResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/admins/permissions');

        $permResponse->assertStatus(200);

        // 2. Create new Admin
        $createResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/admin/admins', [
                'name' => 'Manager Admin',
                'email' => 'manager@kanodia.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => true,
                'permissions' => ['access products'],
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.name', 'Manager Admin')
            ->assertJsonPath('data.email', 'manager@kanodia.com');

        $newAdminId = $createResponse->json('data.id');

        // 3. List admins
        $listResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/admins');

        $listResponse->assertStatus(200);

        // 4. Toggle status
        $toggleResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson("/api/v1/admin/admins/{$newAdminId}/toggle-status");

        $toggleResponse->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 5. Delete Admin
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/v1/admin/admins/{$newAdminId}");

        $deleteResponse->assertStatus(200);
    }
}
