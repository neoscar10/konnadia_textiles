<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductUnit;
use App\Models\ProductCombination;
use App\Models\Cart;
use App\Services\Cart\CartService;
use App\Livewire\Customer\Products\ProductShowPage;
use App\Livewire\Customer\Cart\CartPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class CartFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerLevel $level;
    protected Product $product;
    protected ProductUnit $unit;

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
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
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
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        // Create standard product
        $this->product = Product::create([
            'title' => 'Wholesale Denim Jeans',
            'sku' => 'WDJ-101',
            'base_price' => 1000.00,
            'description' => 'Fine denim jeans.',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 100,
        ]);

        // Create standard unit
        $this->unit = ProductUnit::create([
            'product_id' => $this->product->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'Pcs',
            'conversion_to_base' => 1.0,
        ]);
    }

    public function test_guest_cannot_access_cart_page(): void
    {
        $response = $this->get('/portal/cart');
        $response->assertRedirect('/login');
    }

    public function test_customer_can_add_product_to_cart_via_livewire(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProductShowPage::class, ['slug' => 'wholesale-denim-jeans'])
            ->set('qty', 20)
            ->call('addToCart')
            ->assertDispatched('toast', type: 'success', message: 'Product added to cart successfully.')
            ->assertDispatched('cart-updated');

        $cart = Cart::where('user_id', $this->user->id)->first();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->items);
        $this->assertEquals(20, $cart->items->first()->quantity);
    }

    public function test_same_product_combination_unit_increments_quantity(): void
    {
        $cartService = app(CartService::class);

        // Add once
        $cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);

        // Add again
        $cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 15,
        ]);

        $cart = Cart::where('user_id', $this->user->id)->first();
        $this->assertNotNull($cart);
        $this->assertCount(1, $cart->items);
        $this->assertEquals(25, $cart->items->first()->quantity);
    }

    public function test_cart_totals_calculate_correctly(): void
    {
        $cartService = app(CartService::class);
        $cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10, // Base price 1000, 5% discount = 950. Subtotal = 9500. GST (12%) = 1140. Total = 10640.
        ]);

        $cartData = $cartService->getCartForCustomer($this->user);
        $totals = $cartData['totals'];

        $this->assertEquals(9500.00, (float)$totals['subtotal']);
        $this->assertEquals(1140.00, (float)$totals['gst_amount']);
        $this->assertEquals(10640.00, (float)$totals['total']);
    }

    public function test_cart_item_quantity_can_be_updated(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);
        $item = $cart->items->first();

        Livewire::actingAs($this->user)
            ->test(CartPage::class)
            ->call('updateQuantity', $item->id, 15)
            ->assertDispatched('toast', type: 'success', message: 'Cart updated successfully.');

        $this->assertEquals(15, $item->fresh()->quantity);
    }

    public function test_cart_item_can_be_removed(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->addItem($this->user, [
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'quantity' => 10,
        ]);
        $item = $cart->items->first();

        Livewire::actingAs($this->user)
            ->test(CartPage::class)
            ->call('removeItem', $item->id)
            ->assertDispatched('toast', type: 'success', message: 'Item removed from cart successfully.');

        $this->assertCount(0, $cart->fresh()->items);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $outOfStock = Product::create([
            'title' => 'Out of Stock Product',
            'sku' => 'OOS-999',
            'base_price' => 500,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 0,
        ]);
        
        $oosUnit = ProductUnit::create([
            'product_id' => $outOfStock->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'pcs',
            'conversion_to_base' => 1.0,
        ]);

        Livewire::actingAs($this->user)
            ->test(ProductShowPage::class, ['slug' => 'out-of-stock-product'])
            ->set('qty', 10)
            ->call('addToCart')
            ->assertDispatched('toast', type: 'error', message: 'This product is currently out of stock.');
    }
}
