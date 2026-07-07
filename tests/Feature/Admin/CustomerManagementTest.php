<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Customers\CustomerIndexPage;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $user;
    private CustomerLevel $level;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($customerRole);
        
        $this->level = CustomerLevel::create([
            'name' => 'Wholesale Distributor',
            'discount_percentage' => 10,
            'default_credit_limit' => 500000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
    }

    public function test_guest_cannot_access_customers_page()
    {
        $response = $this->get('/admin/customers');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_customers_page()
    {
        $response = $this->actingAs($this->user)->get('/admin/customers');
        $response->assertRedirect(route('home'));
    }

    public function test_super_admin_can_access_customers_page()
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/customers');
        $response->assertSuccessful();
    }

    public function test_customer_can_be_created_with_linked_user_and_auto_password()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CustomerIndexPage::class)
            ->set('form.company_name', 'Test Company')
            ->set('form.gst_number', '12ABCDE3456F7GH')
            ->set('form.contact_person', 'John Doe')
            ->set('form.mobile_number', '9876543210')
            ->set('form.email', 'john@test.com')
            ->set('form.customer_level_id', $this->level->id)
            ->set('form.credit_limit', 600000)
            ->set('form.allow_credit_beyond_limit', true)
            ->set('form.is_active', true)
            ->set('form.password_mode', 'auto')
            ->call('save');

        // Assert customer profile created
        $this->assertDatabaseHas('customers', [
            'company_name' => 'Test Company',
            'gst_number' => '12ABCDE3456F7GH',
            'credit_limit' => 600000,
            'allow_credit_beyond_limit' => 1,
        ]);

        // Assert user account created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'mobile_number' => '9876543210',
            'is_active' => 1,
        ]);

        $customer = Customer::where('gst_number', '12ABCDE3456F7GH')->first();
        $this->assertNotNull($customer->user_id);

        $user = User::where('mobile_number', '9876543210')->first();
        $this->assertTrue($user->hasRole('customer'));
    }

    public function test_customer_can_be_created_with_manual_password()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CustomerIndexPage::class)
            ->set('form.company_name', 'Manual Test Company')
            ->set('form.gst_number', '12ABCDE3456F8GH')
            ->set('form.contact_person', 'Jane Doe')
            ->set('form.mobile_number', '9876543219')
            ->set('form.email', 'jane@test.com')
            ->set('form.customer_level_id', $this->level->id)
            ->set('form.credit_limit', 200000)
            ->set('form.allow_credit_beyond_limit', false)
            ->set('form.is_active', true)
            ->set('form.password_mode', 'manual')
            ->set('form.password', 'SecretPassword123')
            ->set('form.password_confirmation', 'SecretPassword123')
            ->call('save');

        $this->assertDatabaseHas('customers', [
            'company_name' => 'Manual Test Company',
            'gst_number' => '12ABCDE3456F8GH',
        ]);

        $user = User::where('mobile_number', '9876543219')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('SecretPassword123', $user->password));
    }

    public function test_mobile_number_must_be_unique_across_users()
    {
        // Pre-create user with same phone
        User::factory()->create([
            'email' => 'existing_user@test.com',
            'mobile_number' => '9876543210',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CustomerIndexPage::class)
            ->set('form.company_name', 'Test Company')
            ->set('form.gst_number', '12ABCDE3456F7GH')
            ->set('form.contact_person', 'John Doe')
            ->set('form.mobile_number', '9876543210')
            ->set('form.customer_level_id', $this->level->id)
            ->set('form.credit_limit', 600000)
            ->call('save')
            ->assertHasErrors(['form.mobile_number']);
    }

    public function test_customer_can_be_updated_including_user_details()
    {
        // Setup existing customer & user
        $linkedUser = User::create([
            'name' => 'Anita Desai',
            'email' => 'anita.desai@example.com',
            'mobile_number' => '9876543211',
            'password' => bcrypt('password'),
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
        $linkedUser->assignRole('customer');

        $customer = Customer::create([
            'user_id' => $linkedUser->id,
            'customer_number' => 'KT-002',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Desai Textiles',
            'gst_number' => 'GSTIN-DESAI-002',
            'contact_person' => 'Anita Desai',
            'mobile_number' => '9876543211',
            'email' => 'anita.desai@example.com',
            'credit_limit' => 1500000,
            'available_credit' => 1500000,
            'outstanding_amount' => 0,
            'overdue_amount' => 0,
            'allow_credit_beyond_limit' => true,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CustomerIndexPage::class)
            ->call('edit', $customer->id)
            ->set('form.company_name', 'Desai Textiles Updated')
            ->set('form.contact_person', 'Anita Sen')
            ->set('form.email', 'anita.sen@example.com')
            ->call('save');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company_name' => 'Desai Textiles Updated',
            'contact_person' => 'Anita Sen',
            'email' => 'anita.sen@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $linkedUser->id,
            'name' => 'Anita Sen',
            'email' => 'anita.sen@example.com',
        ]);
    }

    public function test_deactivating_customer_deactivates_linked_user()
    {
        $linkedUser = User::create([
            'name' => 'Ravi Mehta',
            'email' => 'ravi@example.com',
            'mobile_number' => '9876543213',
            'password' => bcrypt('password'),
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
        $linkedUser->assignRole('customer');

        $customer = Customer::create([
            'user_id' => $linkedUser->id,
            'customer_number' => 'KT-004',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Mehta Fashion Traders',
            'gst_number' => 'GSTIN-MEHTA-004',
            'contact_person' => 'Ravi Mehta',
            'mobile_number' => '9876543213',
            'email' => 'ravi@example.com',
            'credit_limit' => 800000,
            'available_credit' => 800000,
            'outstanding_amount' => 0,
            'overdue_amount' => 0,
            'allow_credit_beyond_limit' => false,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CustomerIndexPage::class)
            ->call('toggleStatus', $customer->id);

        $this->assertFalse((bool) $customer->fresh()->is_active);
        $this->assertFalse((bool) $linkedUser->fresh()->is_active);
    }
}
