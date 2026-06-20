<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Livewire\Auth\LoginPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OtpWebLoginTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerLevel $customerLevel;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Web roles
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
     * Test Livewire OTP login flow components.
     */
    public function test_livewire_otp_login_mode_switching(): void
    {
        Livewire::test(LoginPage::class)
            ->assertSet('loginMode', 'password')
            ->call('setLoginMode', 'otp')
            ->assertSet('loginMode', 'otp')
            ->assertSet('otpSent', false);
    }

    /**
     * Test requesting OTP for non-existent user.
     */
    public function test_requesting_otp_for_invalid_identifier_fails(): void
    {
        Livewire::test(LoginPage::class)
            ->call('setLoginMode', 'otp')
            ->set('otpLogin', 'nonexistent@example.com')
            ->call('requestOtp')
            ->assertHasErrors(['otpLogin' => 'No account found with that email or mobile number.'])
            ->assertSet('otpSent', false);
    }

    /**
     * Test requesting OTP for active user success.
     */
    public function test_requesting_otp_for_active_user_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'otpweb@example.com',
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Livewire::test(LoginPage::class)
            ->call('setLoginMode', 'otp')
            ->set('otpLogin', 'otpweb@example.com')
            ->call('requestOtp')
            ->assertHasNoErrors()
            ->assertSet('otpSent', true);
    }

    /**
     * Test verifying OTP and login works.
     */
    public function test_logging_in_with_otp_works(): void
    {
        $user = User::factory()->create([
            'email' => 'otpweb@example.com',
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        // Create Customer profile so customer check passes if relevant (though web redirect works)
        Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-WEB-001',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Web customer',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Web customer',
            'mobile_number' => '9876543210',
            'email' => $user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::test(LoginPage::class)
            ->call('setLoginMode', 'otp')
            ->set('otpLogin', 'otpweb@example.com')
            ->call('requestOtp')
            ->set('otp', '123456')
            ->call('loginWithOtp')
            ->assertHasNoErrors()
            ->assertRedirect('/portal/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test OTP login with invalid digits.
     */
    public function test_logging_in_with_invalid_otp_digits_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'otpweb@example.com',
            'is_active' => true,
        ]);
        $user->assignRole('customer');

        Livewire::test(LoginPage::class)
            ->call('setLoginMode', 'otp')
            ->set('otpLogin', 'otpweb@example.com')
            ->call('requestOtp')
            ->set('otp', '12345') // 5 digits
            ->call('loginWithOtp')
            ->assertHasErrors(['otp']);
    }
}
