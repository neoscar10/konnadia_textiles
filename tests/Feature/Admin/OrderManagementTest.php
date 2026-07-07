<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\OrderPaymentReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\Orders\OrderIndexPage;
use App\Livewire\Admin\Orders\OrderShowPage;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $customerUser;
    protected Customer $customer;
    protected Product $product;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

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
            'customer_number' => 'CUST-ADM-01',
            'customer_level_id' => $level->id,
            'company_name' => 'Admin Test Corp',
            'gst_number' => 'GST-ADM-01',
            'contact_person' => 'Admin Tester',
            'mobile_number' => '9876543210',
            'email' => $this->customerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->product = Product::create([
            'title' => 'Admin Thread',
            'sku' => 'THR-001',
            'base_price' => 50.0,
            'stock_quantity' => 100,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->order = Order::create([
            'order_number' => 'KT-ORD-ADM001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'total_amount' => 1000.0,
        ]);

        OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'unit_name' => 'Meter',
            'unit_short_code' => 'Mtr',
            'unit_conversion_quantity' => 1.0,
            'quantity' => 20,
            'base_unit_price' => 50.0,
            'customer_unit_price' => 50.0,
            'line_total' => 1000.0,
        ]);
    }

    public function test_guest_cannot_access_admin_orders(): void
    {
        $this->get('/admin/orders')->assertRedirect('/login');
    }

    public function test_customer_cannot_access_admin_orders(): void
    {
        $this->actingAs($this->customerUser)->get('/admin/orders')->assertRedirect('/home');
    }

    public function test_admin_can_list_orders(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/orders')
            ->assertStatus(200)
            ->assertSee('Order Management');
    }

    public function test_admin_can_view_order_details(): void
    {
        $this->actingAs($this->adminUser)->get('/admin/orders/' . $this->order->order_number)
            ->assertStatus(200)
            ->assertSee($this->order->order_number);
    }

    public function test_admin_can_mark_order_under_review(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('adminComment', 'Checking verification')
            ->call('markUnderReview')
            ->assertHasNoErrors();

        $this->assertEquals('under_review', $this->order->fresh()->status);
        $this->assertEquals('Checking verification', $this->order->fresh()->admin_note);
    }

    public function test_admin_can_verify_payment_receipt(): void
    {
        // Change order to manual payment
        $this->order->update([
            'status' => 'pending_payment_verification',
            'checkout_method' => 'manual_payment',
            'payment_status' => 'pending_verification',
        ]);

        OrderPaymentReceipt::create([
            'order_id' => $this->order->id,
            'file_path' => 'receipts/test.png',
            'original_name' => 'receipt.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'status' => 'pending_verification',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('adminComment', 'Payment looks good')
            ->call('verifyReceipt')
            ->assertHasNoErrors();

        $this->assertEquals('under_review', $this->order->fresh()->status);
        $this->assertEquals('verified', $this->order->fresh()->payment_status);
    }

    public function test_admin_can_reject_payment_receipt(): void
    {
        $this->order->update([
            'status' => 'pending_payment_verification',
            'checkout_method' => 'manual_payment',
            'payment_status' => 'pending_verification',
        ]);

        OrderPaymentReceipt::create([
            'order_id' => $this->order->id,
            'file_path' => 'receipts/test.png',
            'original_name' => 'receipt.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'status' => 'pending_verification',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('rejectionReason', 'Incorrect transfer amount')
            ->call('rejectReceipt')
            ->assertHasNoErrors();

        $this->assertEquals('rejected', $this->order->fresh()->status);
        $this->assertEquals('rejected', $this->order->fresh()->payment_status);
        $this->assertEquals('Incorrect transfer amount', $this->order->fresh()->rejection_reason);
    }

    public function test_admin_can_approve_order(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('adminComment', 'Approved wholesale order')
            ->call('approveOrder')
            ->assertHasNoErrors();

        $this->assertEquals('approved', $this->order->fresh()->status);
        $this->assertEquals(80, $this->product->fresh()->stock_quantity); // Deducted 20 units
    }

    public function test_admin_can_reject_order(): void
    {
        // Apply credit first to test reversal
        $this->customer->update([
            'outstanding_amount' => 1000.0,
            'available_credit' => 9000.0,
        ]);
        $this->order->update(['credit_applied_at' => now()]);

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('rejectionReason', 'Credit review failed')
            ->call('rejectOrder')
            ->assertHasNoErrors();

        $this->assertEquals('rejected', $this->order->fresh()->status);
        $this->assertEquals(0.0, (float) $this->customer->fresh()->outstanding_amount); // Reversed credit
        $this->assertEquals(10000.0, (float) $this->customer->fresh()->available_credit);
        $this->assertNotNull($this->order->fresh()->credit_reversed_at);
    }
    public function test_admin_can_dispatch_order_item_partially(): void
    {
        // Approve order to deduct stock first (deducts 20 units, stock was 100, becomes 80)
        $this->order->update(['status' => 'approved', 'stock_deducted_at' => now()]);
        $this->product->update(['stock_quantity' => 80]);

        $item = $this->order->items->first();

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('selectedItemId', $item->id)
            ->set('dispatchQty', 5)
            ->call('confirmDispatchItem')
            ->assertHasNoErrors();

        $this->assertEquals('partially_dispatched', $this->order->fresh()->status);

        $items = $this->order->fresh()->items;
        $this->assertCount(2, $items);

        $dispatchedItem = $items->where('status', 'dispatched')->first();
        $pendingItem = $items->where('status', 'pending_dispatch')->first();

        $this->assertNotNull($dispatchedItem);
        $this->assertNotNull($pendingItem);

        $this->assertEquals(5, $dispatchedItem->quantity);
        $this->assertEquals(15, $pendingItem->quantity);
    }

    public function test_admin_can_cancel_remaining_order_item(): void
    {
        // Setup partially dispatched order
        $this->order->update(['status' => 'partially_dispatched', 'stock_deducted_at' => now()]);
        $this->product->update(['stock_quantity' => 80]);

        $item = $this->order->items->first();
        $item->update(['quantity' => 5, 'status' => 'dispatched', 'line_total' => 250.0, 'line_subtotal' => 250.0]);

        $pendingItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'unit_name' => 'Meter',
            'unit_short_code' => 'Mtr',
            'unit_conversion_quantity' => 1.0,
            'quantity' => 15,
            'base_unit_price' => 50.0,
            'customer_unit_price' => 50.0,
            'line_subtotal' => 750.0,
            'line_total' => 750.0,
            'status' => 'pending_dispatch',
        ]);

        // Cancel the remaining pending item
        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('selectedItemId', $pendingItem->id)
            ->call('confirmCancelItem')
            ->assertHasNoErrors();

        // Status should become dispatched as there are no pending items
        $this->assertEquals('dispatched', $this->order->fresh()->status);
        $this->assertEquals('cancelled', $pendingItem->fresh()->status);

        // Stock for the cancelled quantity (15) should be restored: 80 + 15 = 95
        $this->assertEquals(95, $this->product->fresh()->stock_quantity);

        // Order total should be recalculated to exclude the cancelled item: only 250.0 is active
        $this->assertEquals(250.0, (float) $this->order->fresh()->total_amount);
    }
}
