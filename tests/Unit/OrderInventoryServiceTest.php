<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Services\Inventory\OrderInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderInventoryService $inventoryService;
    protected Order $order;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new OrderInventoryService();

        $level = CustomerLevel::create([
            'name' => 'Premium',
            'discount_percentage' => 0,
            'default_credit_limit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $user = \App\Models\User::factory()->create();
        $customer = Customer::create([
            'user_id' => $user->id,
            'customer_number' => 'CUST-INV-01',
            'customer_level_id' => $level->id,
            'company_name' => 'Inventory Test',
            'gst_number' => 'GST-INV-01',
            'contact_person' => 'Inv Tester',
            'mobile_number' => '9876543210',
            'email' => $user->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->product = Product::create([
            'title' => 'Silk Yarn',
            'sku' => 'SLK-YRN-001',
            'base_price' => 100.0,
            'stock_quantity' => 100,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->order = Order::create([
            'order_number' => 'KT-ORD-INV001',
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'total_amount' => 500.0,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'unit_name' => 'Meter',
            'unit_short_code' => 'Mtr',
            'unit_conversion_quantity' => 1.0,
            'quantity' => 20, // requested base quantity = 20
            'base_unit_price' => 100.0,
            'customer_unit_price' => 100.0,
            'line_total' => 2000.0,
        ]);
    }

    public function test_validate_order_stock_returns_correct_availability(): void
    {
        // 20 requested, 100 available -> sufficient
        $validation = $this->inventoryService->validateOrderStock($this->order);
        $this->assertTrue($validation['has_enough_stock']);
        $this->assertEmpty($validation['shortages']);

        // Set stock low so there's a shortage
        $this->product->update(['stock_quantity' => 5]);
        $validationShortage = $this->inventoryService->validateOrderStock($this->order);
        $this->assertFalse($validationShortage['has_enough_stock']);
        $this->assertCount(1, $validationShortage['shortages']);
        $this->assertEquals(15, $validationShortage['shortages'][0]['shortage']);
    }

    public function test_deduct_stock_reduces_inventory(): void
    {
        $this->inventoryService->deductStockForOrder($this->order);
        $this->assertEquals(80, $this->product->fresh()->stock_quantity);
        $this->assertNotNull($this->order->fresh()->stock_deducted_at);
    }

    public function test_restore_stock_returns_inventory(): void
    {
        // Deduct first
        $this->inventoryService->deductStockForOrder($this->order);

        // Restore
        $this->inventoryService->restoreStockForOrder($this->order);
        $this->assertEquals(100, $this->product->fresh()->stock_quantity);
        $this->assertNull($this->order->fresh()->stock_deducted_at);
    }

    public function test_prevents_duplicate_deduction(): void
    {
        $this->inventoryService->deductStockForOrder($this->order);
        $this->assertEquals(80, $this->product->fresh()->stock_quantity);

        // Deduct again -> should not deduct twice
        $this->inventoryService->deductStockForOrder($this->order);
        $this->assertEquals(80, $this->product->fresh()->stock_quantity);
    }
}
