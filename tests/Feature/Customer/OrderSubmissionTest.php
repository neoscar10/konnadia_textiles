<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductUnit;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Livewire\Customer\Orders\OrderReviewPage;
use App\Livewire\Customer\Orders\OrderSuccessPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class OrderSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected CustomerLevel $level;
    protected Product $product;
    protected ProductUnit $unit;
    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        Role::firstOrCreate(['name' => 'customer']);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        $this->level = CustomerLevel::create([
            'name' => 'Bronze Level',
            'discount_percentage' => 0.00, // No discount to make math simple
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-0002',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Test Customer Company',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'John Doe',
            'mobile_number' => '9876543211',
            'email' => $this->user->email,
            'credit_limit' => 20000.00,
            'available_credit' => 20000.00,
            'outstanding_amount' => 0.0,
            'allow_credit_beyond_limit' => false,
            'is_active' => true,
            'billing_address' => 'Surat, Gujarat',
        ]);

        $this->product = Product::create([
            'title' => 'Wholesale Denim Jeans',
            'sku' => 'WDJ-101',
            'base_price' => 1000.00,
            'is_active' => true,
            'stock_quantity' => 100,
        ]);

        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'Pcs',
            'conversion_to_base' => 1.0,
        ]);

        $this->cartService = app(CartService::class);
    }

    public function test_credit_purchase_within_limit_succeeds_with_submitted_status(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10, // Cost = 10 * 1000 = 10,000. GST 12% = 1200. Total = 11,200 (within 20,000 credit limit)
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'credit')
            ->call('submitOrder')
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.orders.success'));

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals('submitted', $order->status);
        $this->assertEquals('not_required', $order->payment_status);
        $this->assertEquals('within_limit', $order->credit_status);
        $this->assertFalse($order->used_credit_override_privilege);

        // Verify credit limit balance updates correctly
        $this->customer->refresh();
        $this->assertEquals(11200.00, (float)$this->customer->outstanding_amount);
        $this->assertEquals(8800.00, (float)$this->customer->available_credit);
    }

    public function test_credit_purchase_over_limit_without_privilege_fails(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 20, // Cost = 20 * 1000 = 20,000. GST 12% = 2400. Total = 22,400 (exceeds 20,000 limit)
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'credit')
            ->call('submitOrder')
            ->assertDispatched('toast', type: 'error', message: 'This order exceeds your available credit limit. Please reduce your cart value or choose manual payment with receipt upload.');

        $this->assertEquals(0, Order::count());
        
        $this->customer->refresh();
        $this->assertEquals(0.00, (float)$this->customer->outstanding_amount);
        $this->assertEquals(20000.00, (float)$this->customer->available_credit);
    }

    public function test_credit_purchase_over_limit_with_privilege_succeeds_with_override_status(): void
    {
        // Give the customer privilege to go beyond their limit
        $this->customer->update(['allow_credit_beyond_limit' => true]);

        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 20, // Total = 22,400 (exceeds 20,000 limit but allowed due to privilege)
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'credit')
            ->call('submitOrder')
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.orders.success'));

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals('submitted', $order->status); // Wait, status remains 'submitted' according to CheckoutService
        $this->assertEquals('over_limit_allowed', $order->credit_status);
        $this->assertTrue($order->used_credit_override_privilege);

        // Credit owed must still be calculated correctly (outstanding amount can exceed credit limit)
        $this->customer->refresh();
        $this->assertEquals(22400.00, (float)$this->customer->outstanding_amount);
        $this->assertEquals(-2400.00, (float)$this->customer->available_credit); // Correctly negative
    }

    public function test_cart_is_marked_converted_after_order_submission(): void
    {
        $cart = $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        $this->assertEquals('active', $cart->status);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'credit')
            ->call('submitOrder');

        $this->assertEquals('converted', $cart->fresh()->status);
    }

    public function test_order_items_snapshot_product_data(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'credit')
            ->call('submitOrder');

        $order = Order::first();
        $this->assertCount(1, $order->items);
        
        $item = $order->items->first();
        $this->assertEquals('Wholesale Denim Jeans', $item->product_title);
        $this->assertEquals('WDJ-101', $item->product_sku);
        $this->assertEquals('Piece', $item->unit_name);
        $this->assertEquals(10, $item->quantity);
        $this->assertEquals(1000.00, (float)$item->customer_unit_price);
        $this->assertEquals(10000.00, (float)$item->line_subtotal);
        $this->assertEquals(1200.00, (float)$item->gst_amount);
        $this->assertEquals(11200.00, (float)$item->line_total);
    }

    public function test_order_success_page_loads_with_real_order_data(): void
    {
        $orderData = [
            'order_number' => 'KT-ORD-999999',
            'checkout_method' => 'credit',
            'total_amount' => 11200.00,
            'payment_status' => 'not_required',
            'credit_status' => 'within_limit',
            'used_credit_override' => false,
        ];

        // Accessing the success page with session data
        $response = $this->actingAs($this->user)
            ->withSession(['order_success' => $orderData])
            ->get(route('customer.orders.success'));

        $response->assertStatus(200);
        $response->assertSee('KT-ORD-999999');
        $response->assertSee('11,200.00');
    }
}
