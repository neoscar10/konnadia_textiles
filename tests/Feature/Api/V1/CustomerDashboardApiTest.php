<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CustomerDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected User $adminUser;
    protected Customer $customer;
    protected CustomerLevel $customerLevel;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Gold Tier',
            'discount_percentage' => 15.00,
            'default_credit_limit' => 100000.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customerUser = User::factory()->create(['is_active' => true]);
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-DB-99',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Dashboard Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Dashboard Contact',
            'mobile_number' => '9000000010',
            'email' => $this->customerUser->email,
            'credit_limit' => 200000.00,
            'outstanding_amount' => 50000.00,
            'available_credit' => 150000.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('super_admin');
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $this->getJson('/api/v1/dashboard')->assertStatus(401);
    }

    public function test_admin_gets_403_on_customer_dashboard(): void
    {
        $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/dashboard')
            ->assertStatus(403);
    }

    public function test_active_customer_receives_correct_dashboard_json_structure(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => [
                        'id',
                        'customer_number',
                        'company_name',
                        'contact_person',
                        'level',
                        'is_active',
                    ],
                    'credit' => [
                        'credit_limit',
                        'outstanding_amount',
                        'available_credit',
                        'overdue_amount',
                        'credit_hold',
                        'status',
                        'risk_level',
                        'utilization_percentage',
                        'formatted_available_credit',
                    ],
                    'cart',
                    'orders',
                    'recent_orders',
                    'alerts',
                    'quick_actions',
                    'recommended_products',
                ]
            ]);
    }
}
