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
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Livewire\Customer\Orders\OrderReviewPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
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
            'discount_percentage' => 5.00,
            'is_active' => true,
        ]);

        Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-0002',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Test Customer Company',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'John Doe',
            'mobile_number' => '9876543211',
            'email' => $this->user->email,
            'credit_limit' => 10000.00,
            'available_credit' => 10000.00,
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
        Storage::fake('public');
    }

    public function test_empty_cart_cannot_proceed_to_checkout(): void
    {
        // Active cart is empty, should redirect to cart page
        $response = $this->actingAs($this->user)->get('/portal/order/review');
        $response->assertRedirect('/portal/cart');
    }

    public function test_checkout_summary_loads_correctly_when_cart_not_empty(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->get('/portal/order/review');
        $response->assertStatus(200);
        $response->assertSee('Review &amp; Submit Order');
    }

    public function test_manual_payment_requires_receipt_file_upload(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'manual_payment')
            ->set('receiptFile', null)
            ->call('submitOrder')
            ->assertDispatched('toast', type: 'error', message: 'Please upload a valid payment receipt.');

        $this->assertEquals(0, Order::count());
    }

    public function test_manual_payment_creates_order_with_pending_payment_verification_status(): void
    {
        $this->cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        $file = UploadedFile::fake()->image('payment_receipt.jpg', 800, 600)->size(500);

        Livewire::actingAs($this->user)
            ->test(OrderReviewPage::class)
            ->set('checkoutMethod', 'manual_payment')
            ->set('receiptFile', $file)
            ->call('submitOrder')
            ->assertHasNoErrors()
            ->assertRedirect(route('customer.orders.success'));

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals('pending_payment_verification', $order->status);
        $this->assertEquals('pending_verification', $order->payment_status);
        $this->assertEquals('manual_payment', $order->checkout_method);
        
        $this->assertCount(1, $order->receipts);
        $this->assertNotNull($order->receipts->first()->file_path);
        Storage::disk('public')->assertExists($order->receipts->first()->file_path);
    }
}
