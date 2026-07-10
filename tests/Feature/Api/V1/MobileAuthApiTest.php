<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileAuthApiTest extends TestCase
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
     * Test successful login by email.
     */
    public function test_customer_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-001',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Test Saree Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Rajesh',
            'mobile_number' => '9876543210',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'customer@example.com',
            'password' => 'password123',
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
                    'customer' => [
                        'id', 'customer_number', 'company_name', 'gst_number',
                        'contact_person', 'mobile_number', 'email',
                        'customer_level' => ['id', 'name', 'discount_percentage'],
                        'credit' => ['credit_limit', 'outstanding_amount', 'available_credit', 'overdue_amount', 'allow_credit_beyond_limit'],
                        'is_active'
                    ],
                    'navigation' => ['default_screen', 'can_access_products', 'can_place_orders']
                ]
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test successful login by mobile number.
     */
    public function test_customer_can_login_with_mobile_number(): void
    {
        $user = User::factory()->create([
            'email' => 'mobileuser@example.com',
            'mobile_number' => '9876543210',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-002',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Mobile Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Suresh',
            'mobile_number' => '9876543210',
            'email' => 'mobileuser@example.com',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => '9876543210',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test invalid credentials.
     */
    public function test_invalid_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid login credentials.');
    }

    /**
     * Test admin users are forbidden from mobile API login.
     */
    public function test_admin_is_forbidden_from_mobile_api(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@kannodia.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('super_admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@kannodia.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This account is not allowed to access the mobile app.');
    }

    /**
     * Test inactive user is blocked.
     */
    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-003',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Inactive Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Ramesh',
            'mobile_number' => '9000000003',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is inactive. Please contact support.');
    }

    /**
     * Test user without customer profile is blocked.
     */
    public function test_user_without_customer_profile_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'noprofile@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'noprofile@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Customer profile not found for this account.');
    }

    /**
     * Test authenticated user endpoint /me.
     */
    public function test_me_returns_profile_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'me@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-004',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Me Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Me Person',
            'mobile_number' => '9000000004',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'me@example.com')
            ->assertJsonPath('data.customer.company_name', 'Me Shop');
    }

    /**
     * Test /me rejects missing token.
     */
    public function test_me_rejects_missing_token(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Authentication token is missing.');
    }

    /**
     * Test token refresh.
     */
    public function test_refresh_returns_new_token(): void
    {
        $user = User::factory()->create([
            'email' => 'refresh@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-005',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Refresh Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Refresh Person',
            'mobile_number' => '9000000005',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'token_type', 'expires_in']
            ]);

        $newToken = $response->json('data.token');
        $this->assertNotEquals($token, $newToken);
    }

    /**
     * Test logout.
     */
    public function test_logout_invalidates_token(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-006',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Logout Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Logout Person',
            'mobile_number' => '9000000006',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $token = auth('api')->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logout successful.');

        // Token should now be invalid
        $meResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $meResponse->assertStatus(401);
    }

    /**
     * Test change password.
     */
    public function test_change_password_requires_correct_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'pwd@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-007',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Password Shop',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Password Person',
            'mobile_number' => '9000000007',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $token = auth('api')->login($user);

        // Wrong current password
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrong_current',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Current password is incorrect.');

        // Correct change password
        $responseCorrect = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/change-password', [
            'current_password' => 'oldpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $responseCorrect->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password changed successfully. Please log in again.');

        // Verify password actually updated
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test forgot password foundation.
     */
    public function test_forgot_password_generic_response(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'login' => '9876543210',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'If the account exists, password reset instructions will be sent.');
    }
}
