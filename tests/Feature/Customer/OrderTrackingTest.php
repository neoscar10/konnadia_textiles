<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Customer\Orders\OrderIndexPage;
use App\Livewire\Customer\Orders\OrderShowPage;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected User $otherCustomerUser;
    protected Customer $customer;
    protected Customer $otherCustomer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $this->otherCustomerUser = User::factory()->create();
        $this->otherCustomerUser->assignRole('customer');

        $level = CustomerLevel::create([
            'name' => 'Premium',
            'discount_percentage' => 0,
            'default_credit_limit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-TRK-01',
            'customer_level_id' => $level->id,
            'company_name' => 'Tracking Test Corp',
            'gst_number' => 'GST-TRK-01',
            'contact_person' => 'Track Tester',
            'mobile_number' => '9876543210',
            'email' => $this->customerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->otherCustomer = Customer::create([
            'user_id' => $this->otherCustomerUser->id,
            'customer_number' => 'CUST-TRK-02',
            'customer_level_id' => $level->id,
            'company_name' => 'Other Corp',
            'gst_number' => 'GST-TRK-02',
            'contact_person' => 'Other Tester',
            'mobile_number' => '9876543211',
            'email' => $this->otherCustomerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->order = Order::create([
            'order_number' => 'KT-ORD-TRK001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'total_amount' => 1000.0,
            'submitted_at' => now(),
        ]);
    }

    public function test_guest_cannot_access_customer_orders(): void
    {
        $this->get('/portal/orders')->assertRedirect('/login');
    }

    public function test_customer_can_list_own_orders(): void
    {
        $this->actingAs($this->customerUser)->get('/portal/orders')
            ->assertStatus(200)
            ->assertSee($this->order->order_number);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $this->actingAs($this->otherCustomerUser)
            ->get('/portal/orders/' . $this->order->order_number)
            ->assertStatus(404);
    }

    public function test_customer_can_view_own_order_detail(): void
    {
        $this->actingAs($this->customerUser)
            ->get('/portal/orders/' . $this->order->order_number)
            ->assertStatus(200)
            ->assertSee($this->order->order_number);
    }

    public function test_rejected_order_shows_rejection_reason(): void
    {
        $this->order->update([
            'status' => 'rejected',
            'rejection_reason' => 'Invalid payment reference details.',
        ]);

        $this->actingAs($this->customerUser)
            ->get('/portal/orders/' . $this->order->order_number)
            ->assertStatus(200)
            ->assertSee('Invalid payment reference details.');
    }
}
