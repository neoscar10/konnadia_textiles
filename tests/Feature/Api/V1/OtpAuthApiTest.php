<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OtpAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerLevel $customerLevel;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create API roles
        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Gold Level',
            'discount_percentage' => 10.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
    }

    /**
     * Helper to create a customer.
     */
    protected function createCustomer(array $userAttributes = [], array $customerAttributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'email' => 'customer@example.com',
            'is_active' => true,
        ], $userAttributes));
        $user->assignRole('customer');

        Customer::create(array_merge([
            'user_id' => $user->id,
            'customer_number' => 'CUST-OTP-001',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Test OTP Saree Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'OTP Rajesh',
            'mobile_number' => '9876543210',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ], $customerAttributes));

        return $user;
    }

    /**
     * Test requesting OTP successfully.
     */
    public function test_customer_can_request_otp(): void
    {
        $this->createCustomer(['email' => 'otpuser@example.com']);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'login' => 'otpuser@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'OTP sent successfully. Any 6-digit code will pass.');
    }

    /**
     * Test requesting OTP with mobile number.
     */
    public function test_customer_can_request_otp_by_mobile(): void
    {
        $this->createCustomer(['mobile_number' => '9000000001'], ['mobile_number' => '9000000001']);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'login' => '9000000001',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test login via OTP works with any 6 digits.
     */
    public function test_customer_can_login_with_valid_otp(): void
    {
        $user = $this->createCustomer(['email' => 'otpuser@example.com']);

        $response = $this->postJson('/api/v1/auth/otp/login', [
            'login' => 'otpuser@example.com',
            'otp' => '123456',
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
                    'user' => ['id', 'name', 'email', 'mobile_number', 'roles', 'is_active'],
                    'customer' => ['id', 'company_name', 'customer_number'],
                ]
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test login fails with invalid length or non-digits.
     */
    public function test_login_fails_with_invalid_otp_format(): void
    {
        $this->createCustomer(['email' => 'otpuser@example.com']);

        // Invalid length (5 digits)
        $response1 = $this->postJson('/api/v1/auth/otp/login', [
            'login' => 'otpuser@example.com',
            'otp' => '12345',
        ]);
        $response1->assertStatus(422);

        // Non-numeric
        $response2 = $this->postJson('/api/v1/auth/otp/login', [
            'login' => 'otpuser@example.com',
            'otp' => 'abcdef',
        ]);
        $response2->assertStatus(422);
    }

    /**
     * Test admin user is blocked.
     */
    public function test_admin_is_forbidden_from_otp_api(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@kannodia.com',
            'is_active' => true,
        ]);
        $admin->assignRole('super_admin');

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'login' => 'admin@kannodia.com',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test inactive customer user is blocked.
     */
    public function test_inactive_customer_cannot_use_otp(): void
    {
        $this->createCustomer(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/otp/send', [
            'login' => 'customer@example.com',
        ]);

        $response->assertStatus(403);
    }
}
