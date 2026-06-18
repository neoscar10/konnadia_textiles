<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Services\Customer\Dashboard\CustomerDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CustomerDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerDashboardService $service;
    protected User $user;
    protected CustomerLevel $level;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CustomerDashboardService::class);

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        $this->level = CustomerLevel::create([
            'name' => 'Platinum Level',
            'discount_percentage' => 20.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-PLAT-7',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Apex Platinum',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Jane Admin',
            'mobile_number' => '9876543233',
            'email' => $this->user->email,
            'credit_limit' => 1000000.00,
            'outstanding_amount' => 500000.00,
            'available_credit' => 500000.00,
            'overdue_amount' => 0.00,
            'is_active' => true,
        ]);
    }

    public function test_currency_formatting_applies_indian_system()
    {
        $this->assertEquals('₹5,00,000.00', $this->service->formatIndianCurrency(500000.00));
        $this->assertEquals('₹12,34,567.89', $this->service->formatIndianCurrency(1234567.89));
        $this->assertEquals('₹100.00', $this->service->formatIndianCurrency(100.00));
    }

    public function test_credit_summary_computes_correctly()
    {
        $summary = $this->service->getCreditSummary($this->user);

        $this->assertEquals(1000000.00, $summary['credit_limit']);
        $this->assertEquals(500000.00, $summary['outstanding_amount']);
        $this->assertEquals(50.0, $summary['utilization_percentage']);
        $this->assertEquals('₹5,00,000.00', $summary['formatted_available_credit']);
        $this->assertEquals('healthy', $summary['status']['value']);
    }

    public function test_cart_summary_fetches_totals_and_counts()
    {
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'active'
        ]);

        $product = Product::create([
            'title' => 'Fabric Blue',
            'sku' => 'FAB-BLU',
            'base_price' => 500.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Meter',
            'short_code' => 'Mtr',
            'level' => 1,
            'conversion_to_base' => 1.0,
            'multiplier' => 1.0,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_unit_id' => $unit->id,
            'quantity' => 10,
            'unit_conversion_quantity' => 1.0,
            'base_unit_price' => 500.00,
            'customer_unit_price' => 400.00, // platinum level 20% discount
            'line_subtotal' => 4000.00,
            'gst_percentage' => 12.0,
            'gst_amount' => 480.00,
            'line_total' => 4480.00,
        ]);

        $summary = $this->service->getCartSummary($this->user);

        $this->assertTrue($summary['exists']);
        $this->assertEquals(1, $summary['items_count']);
        $this->assertEquals(4480.00, $summary['total_amount']);
        $this->assertEquals('₹4,480.00', $summary['formatted_total_amount']);
    }
}
