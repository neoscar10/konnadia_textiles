<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Order $order;
    protected OrderItem $item1;
    protected OrderItem $item2;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $level = CustomerLevel::create([
            'name' => 'Standard Level',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('customer');

        Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-ITEM-001',
            'customer_level_id' => $level->id,
            'company_name' => 'Item Status Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Item Person',
            'mobile_number' => '9000000001',
            'email' => $this->user->email,
            'is_active' => true,
        ]);

        $this->user->refresh();

        $product1 = Product::create([
            'title' => 'Product Alpha',
            'sku' => 'SKU-ALPHA',
            'base_price' => 1000,
            'is_active' => true,
        ]);

        $product2 = Product::create([
            'title' => 'Product Beta',
            'sku' => 'SKU-BETA',
            'base_price' => 2000,
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->user->customer->id,
            'order_number' => 'KT-ORD-TEST-999',
            'status' => 'partially_dispatched',
            'subtotal' => 3000,
            'gst_amount' => 360,
            'total_amount' => 3360,
            'checkout_method' => 'manual_payment',
            'payment_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->item1 = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product1->id,
            'product_title' => $product1->title,
            'product_sku' => $product1->sku,
            'unit_name' => 'Piece',
            'unit_short_code' => 'Pcs',
            'unit_conversion_quantity' => 1,
            'quantity' => 5,
            'base_unit_price' => 1000,
            'customer_unit_price' => 1000,
            'line_subtotal' => 5000,
            'gst_percentage' => 12,
            'gst_amount' => 600,
            'line_total' => 5600,
            'status' => 'dispatched',
            'dispatch_number' => 'DISP-1001',
            'dispatch_note' => 'Dispatched via DTDC Express',
            'dispatched_at' => now(),
        ]);

        $this->item2 = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $product2->id,
            'product_title' => $product2->title,
            'product_sku' => $product2->sku,
            'unit_name' => 'Piece',
            'unit_short_code' => 'Pcs',
            'unit_conversion_quantity' => 1,
            'quantity' => 2,
            'base_unit_price' => 2000,
            'customer_unit_price' => 2000,
            'line_subtotal' => 4000,
            'gst_percentage' => 12,
            'gst_amount' => 480,
            'line_total' => 4480,
            'status' => 'pending_dispatch',
        ]);
    }

    public function test_api_order_detail_returns_item_level_status_and_dispatch_info()
    {
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/orders/' . $this->order->order_number);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', 'KT-ORD-TEST-999')
            ->assertJsonPath('data.status', 'partially_dispatched')
            ->assertJsonPath('data.items.0.status.value', 'dispatched')
            ->assertJsonPath('data.items.0.status.label', 'Dispatched')
            ->assertJsonPath('data.items.0.dispatch.dispatch_number', 'DISP-1001')
            ->assertJsonPath('data.items.0.dispatch.dispatch_note', 'Dispatched via DTDC Express')
            ->assertJsonPath('data.items.1.status.value', 'pending_dispatch')
            ->assertJsonPath('data.items.1.status.label', 'Pending Dispatch');
    }
}
