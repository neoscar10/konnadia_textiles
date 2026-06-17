<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductUnit;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected User $adminUser;
    protected Customer $customer;
    protected CustomerLevel $customerLevel;
    protected Product $product;
    protected ProductUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Silver Tier',
            'discount_percentage' => 10.00,
            'default_credit_limit' => 50000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-CAR-01',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Cart Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Cart Contact',
            'mobile_number' => '9000000002',
            'email' => $this->customerUser->email,
            'credit_limit' => 50000,
            'outstanding_amount' => 0.0,
            'available_credit' => 50000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->product = Product::create([
            'title' => 'Test Fabric Product',
            'sku' => 'FAB-001',
            'base_price' => 1000.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 100,
        ]);

        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Meter',
            'short_code' => 'Mtr',
            'level' => 1,
            'conversion_to_base' => 1.00,
            'multiplier' => 1.00,
        ]);
    }

    public function test_guest_cannot_access_cart(): void
    {
        $this->getJson('/api/v1/cart')->assertStatus(401);
    }

    public function test_admin_cannot_access_cart(): void
    {
        $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/cart')
            ->assertStatus(403);
    }

    public function test_inactive_customer_cannot_access_cart(): void
    {
        $this->customer->update(['is_active' => false]);

        $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/cart')
            ->assertStatus(403);
    }

    public function test_can_view_cart(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'status',
                    'items_count',
                    'items',
                    'summary'
                ]
            ]);
    }

    public function test_can_add_item_to_cart(): void
    {
        $payload = [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->postJson('/api/v1/cart/items', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            // Silver tier gets 10% discount: 1000 * 0.9 = 900
            ->assertJsonPath('data.items.0.pricing.customer_unit_price', 900)
            ->assertJsonPath('data.items.0.quantity', 10);
    }

    public function test_can_update_cart_item(): void
    {
        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 5,
            'unit_conversion_quantity' => 1.0,
            'base_unit_price' => 1000.0,
            'customer_unit_price' => 900.0,
            'line_subtotal' => 4500.0,
            'gst_percentage' => 5.0,
            'gst_amount' => 225.0,
            'line_total' => 4725.0,
        ]);

        $payload = [
            'quantity' => 15,
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->patchJson('/api/v1/cart/items/' . $item->id, $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.items.0.quantity', 15);
    }

    public function test_can_remove_cart_item(): void
    {
        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 5,
            'unit_conversion_quantity' => 1.0,
            'base_unit_price' => 1000.0,
            'customer_unit_price' => 900.0,
            'line_subtotal' => 4500.0,
            'gst_percentage' => 5.0,
            'gst_amount' => 225.0,
            'line_total' => 4725.0,
        ]);

        $response = $this->actingAs($this->customerUser, 'api')
            ->deleteJson('/api/v1/cart/items/' . $item->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_can_clear_cart(): void
    {
        $cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 5,
            'unit_conversion_quantity' => 1.0,
            'base_unit_price' => 1000.0,
            'customer_unit_price' => 900.0,
            'line_subtotal' => 4500.0,
            'gst_percentage' => 5.0,
            'gst_amount' => 225.0,
            'line_total' => 4725.0,
        ]);

        $response = $this->actingAs($this->customerUser, 'api')
            ->deleteJson('/api/v1/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data.items');
    }
}
