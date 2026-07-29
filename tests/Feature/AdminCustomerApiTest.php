<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdminCustomerApiTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerLevel $level;
    protected User $superAdmin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);

        Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access customers', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'access customer-levels', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access customer-levels', 'guard_name' => 'api']);

        $this->level = CustomerLevel::create([
            'name' => 'Wholesale Tier A',
            'discount_percentage' => 15.00,
            'default_credit_limit' => 50000.00,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@kanodia.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->token = auth('api')->login($this->superAdmin);
    }

    public function test_super_admin_can_create_customer_level(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/customer-levels', [
                'name' => 'VIP Tier B',
                'discount_percentage' => 20.00,
                'default_credit_limit' => 100000.00,
                'sort_order' => 2,
                'is_active' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'VIP Tier B')
            ->assertJsonPath('data.discount_percentage', 20);
    }

    public function test_super_admin_can_create_and_manage_customer(): void
    {
        // 1. Create Customer
        $createResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/admin/customers', [
                'customer_level_id' => $this->level->id,
                'company_name' => 'Apex Fabrics Pvt Ltd',
                'gst_number' => '29ABCDE1234F1Z5',
                'contact_person' => 'Rajesh Sharma',
                'mobile_number' => '9876543210',
                'email' => 'rajesh@apexfabrics.com',
                'credit_limit' => 75000,
                'allow_credit_beyond_limit' => false,
                'address' => '123 Textile Market',
                'city' => 'Surat',
                'state' => 'Gujarat',
                'pincode' => '395002',
                'is_active' => true,
                'password_mode' => 'auto',
            ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.company_name', 'Apex Fabrics Pvt Ltd')
            ->assertJsonPath('data.gst_number', '29ABCDE1234F1Z5');

        $customerId = $createResponse->json('data.id');

        // 2. Fetch Customer Details
        $showResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/admin/customers/{$customerId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.contact_person', 'Rajesh Sharma');

        // 3. Update Customer
        $updateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->putJson("/api/v1/admin/customers/{$customerId}", [
                'company_name' => 'Apex Fabrics International',
                'contact_person' => 'Rajesh Kumar Sharma',
                'credit_limit' => 90000,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.company_name', 'Apex Fabrics International');

        // 4. Toggle Status
        $toggleResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/v1/admin/customers/{$customerId}/toggle-status");

        $toggleResponse->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // 5. Delete Customer
        $deleteResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->deleteJson("/api/v1/admin/customers/{$customerId}");

        $deleteResponse->assertStatus(200);
    }
}
