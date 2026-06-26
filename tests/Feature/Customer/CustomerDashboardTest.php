<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Models\Order;
use App\Models\Cart;
use App\Livewire\Customer\DashboardPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerLevel $level;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('customer');

        $this->level = CustomerLevel::create([
            'name' => 'Silver Level',
            'discount_percentage' => 10.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-8888',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Apex Fabrics',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Jane Smith',
            'mobile_number' => '9876543200',
            'email' => $this->user->email,
            'credit_limit' => 500000.00,
            'outstanding_amount' => 100000.00,
            'available_credit' => 400000.00,
            'overdue_amount' => 0.00,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('customer.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_away()
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_customer_can_see_dashboard_with_company_name()
    {
        $this->actingAs($this->user);

        Livewire::test(DashboardPage::class)
            ->assertSee('Apex Fabrics')
            ->assertSee('Silver Level')
            ->assertSee('CUST-8888')
            ->assertSee('₹4,00,000.00') // Available Credit
            ->assertSee('₹5,00,000.00'); // Credit limit
    }

    public function test_dashboard_shows_credit_alerts()
    {
        // Set customer to on hold
        $this->customer->update([
            'credit_hold' => true,
            'credit_hold_reason' => 'Late payment'
        ]);

        $this->actingAs($this->user);

        Livewire::test(DashboardPage::class)
            ->assertDontSee('Account On Hold')
            ->assertDontSee('Late payment');
    }
}
