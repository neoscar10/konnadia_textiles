<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductUnit;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class CheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected CustomerLevel $customerLevel;
    protected Product $product;
    protected ProductUnit $unit;
    protected Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Storage::fake('public');

        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Premium Tier',
            'discount_percentage' => 20.00,
            'default_credit_limit' => 10000,
            'is_active' => true,
        ]);

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-CHK-01',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'Checkout Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Checkout Contact',
            'mobile_number' => '9000000003',
            'email' => $this->customerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'title' => 'Test Cotton Product',
            'sku' => 'COT-001',
            'base_price' => 100.00,
            'is_active' => true,
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

        // Create active cart with item
        $this->cart = Cart::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $this->cart->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->unit->id,
            'quantity' => 50,
            'unit_conversion_quantity' => 1.0,
            'base_unit_price' => 100.0,
            'customer_unit_price' => 80.0, // 20% discount
            'line_subtotal' => 4000.0,
            'gst_percentage' => 5.0,
            'gst_amount' => 200.0,
            'line_total' => 4200.0, // order total is 4200
        ]);
    }

    public function test_can_view_checkout_summary(): void
    {
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/checkout/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cart',
                    'customer_credit',
                    'credit_eligibility',
                    'checkout_methods'
                ]
            ]);
    }

    public function test_can_checkout_with_manual_payment(): void
    {
        $receipt = UploadedFile::fake()->image('receipt.png');

        $payload = [
            'checkout_method' => 'manual_payment',
            'receipt_file' => $receipt,
            'customer_notes' => 'Please ship ASAP.',
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->postJson('/api/v1/checkout/submit', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending_payment_verification')
            ->assertJsonPath('data.payment_status', 'pending_verification')
            ->assertJsonPath('data.customer_notes', 'Please ship ASAP.');

        $this->assertEquals('converted', $this->cart->fresh()->status);
        $this->assertCount(1, Order::all());
    }

    public function test_can_checkout_with_credit_within_limit(): void
    {
        $payload = [
            'checkout_method' => 'credit',
            'customer_notes' => 'Using credit tier.',
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->postJson('/api/v1/checkout/submit', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.credit_status', 'within_limit');

        $this->assertEquals(4480.0, (float) $this->customer->fresh()->outstanding_amount);
        $this->assertEquals(5520.0, (float) $this->customer->fresh()->available_credit);
    }

    public function test_credit_checkout_fails_if_limit_exceeded_and_no_override(): void
    {
        // Change quantity to exceed 10000 limit
        $item = $this->cart->items->first();
        $item->update([
            'quantity' => 150,
            'line_subtotal' => 12000.0,
            'gst_amount' => 600.0,
            'line_total' => 12600.0,
        ]);

        $this->customer->update([
            'allow_credit_beyond_limit' => false,
        ]);

        $payload = [
            'checkout_method' => 'credit',
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->postJson('/api/v1/checkout/submit', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors']);
    }

    public function test_credit_checkout_allowed_if_limit_exceeded_but_override_true(): void
    {
        // Change quantity to exceed 10000 limit
        $item = $this->cart->items->first();
        $item->update([
            'quantity' => 150,
            'line_subtotal' => 12000.0,
            'gst_amount' => 600.0,
            'line_total' => 12600.0,
        ]);

        $this->customer->update([
            'allow_credit_beyond_limit' => true,
        ]);

        $payload = [
            'checkout_method' => 'credit',
        ];

        $response = $this->actingAs($this->customerUser, 'api')
            ->postJson('/api/v1/checkout/submit', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.credit_status', 'over_limit_allowed')
            ->assertJsonPath('data.used_credit_override_privilege', true);
    }
}
