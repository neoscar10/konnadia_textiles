<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected CustomerLevel $customerLevel;
    protected Product $product;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Premium Tier',
            'discount_percentage' => 20.00,
            'default_credit_limit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-ORD-01',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Order Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Order Contact',
            'mobile_number' => '9000000004',
            'email' => $this->customerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->product = Product::create([
            'title' => 'Test Cotton Product',
            'sku' => 'COT-001',
            'base_price' => 100.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 100,
        ]);

        // Create a test order
        $this->order = Order::create([
            'order_number' => 'ORD-2026-0001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'payment_status' => 'not_required',
            'credit_status' => 'within_limit',
            'subtotal' => 4000.0,
            'gst_amount' => 200.0,
            'total_amount' => 4200.0,
            'submitted_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'unit_name' => 'Meter',
            'unit_short_code' => 'Mtr',
            'unit_conversion_quantity' => 1.0,
            'quantity' => 50,
            'base_unit_price' => 100.0,
            'customer_unit_price' => 80.0,
            'line_subtotal' => 4000.0,
            'gst_percentage' => 5.0,
            'gst_amount' => 200.0,
            'line_total' => 4200.0,
        ]);

        // Status history
        $this->order->statusHistories()->create([
            'from_status' => null,
            'to_status' => 'submitted',
            'note' => 'Order submitted by customer.',
            'changed_by' => $this->customerUser->id,
        ]);
    }

    public function test_can_list_orders(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-2026-0001');
    }

    public function test_can_filter_orders_by_status(): void
    {
        // Another order with different status
        Order::create([
            'order_number' => 'ORD-2026-0002',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'approved',
            'checkout_method' => 'credit',
            'payment_status' => 'not_required',
            'total_amount' => 1000.0,
            'submitted_at' => now(),
        ]);

        // Filter status = submitted
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders?status=submitted');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-2026-0001');

        // Filter status = approved
        $responseApproved = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders?status=approved');

        $responseApproved->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-2026-0002');
    }

    public function test_can_search_orders_by_number(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders?search=2026-0001');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-2026-0001');

        $responseEmpty = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders?search=nonexistent');

        $responseEmpty->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_can_view_order_details(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/orders/' . $this->order->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', 'ORD-2026-0001')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product.title', 'Test Cotton Product')
            ->assertJsonCount(1, 'data.timeline')
            ->assertJsonPath('data.timeline.0.note', 'Order submitted by customer.');
    }
}
