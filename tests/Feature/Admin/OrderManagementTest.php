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
        $this->actingAs($this->customerUser)->get('/admin/orders')->assertRedirect(route('home'));
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
        $this->assertEquals(100, $this->product->fresh()->stock_quantity); // Stock NOT deducted on approval
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
        // Approve order. Stock is not deducted on approval (remains 100)
        $this->order->update(['status' => 'approved']);
        $this->product->update(['stock_quantity' => 100]);

        $item = $this->order->items->first();

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('selectedItemId', $item->id)
            ->set('dispatchQty', 5)
            ->call('confirmDispatchItem')
            ->assertHasNoErrors();

        $this->assertEquals('partially_dispatched', $this->order->fresh()->status);
        // Stock should be deducted only for the dispatched 5 units (100 - 5 = 95)
        $this->assertEquals(95, $this->product->fresh()->stock_quantity);

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
        $this->order->update(['status' => 'partially_dispatched']);
        // 5 units were dispatched (so stock was reduced to 95). 15 units are pending (no stock deducted yet)
        $this->product->update(['stock_quantity' => 95]);

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

        // Stock should remain 95 (no stock restore needed since the pending 15 units were never deducted)
        $this->assertEquals(95, $this->product->fresh()->stock_quantity);

        // Order total should be recalculated to exclude the cancelled item: only 250.0 is active
        $this->assertEquals(250.0, (float) $this->order->fresh()->total_amount);
    }

    public function test_admin_can_dispatch_order_item_with_dispatch_note(): void
    {
        $this->order->update(['status' => 'approved']);
        $item = $this->order->items->first();

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('selectedItemId', $item->id)
            ->set('dispatchQty', 5)
            ->set('dispatchNote', 'Disp note 1')
            ->call('confirmDispatchItem')
            ->assertHasNoErrors();

        $dispatchedItem = $this->order->fresh()->items->where('status', 'dispatched')->first();
        $this->assertNotNull($dispatchedItem);
        $this->assertEquals('Disp note 1', $dispatchedItem->dispatch_note);
    }

    public function test_admin_can_bulk_dispatch_manufactured_items(): void
    {
        $this->order->update(['status' => 'approved']);
        
        $item1 = $this->order->items->first();
        
        $item2 = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_title' => $this->product->title,
            'product_sku' => $this->product->sku,
            'unit_name' => 'Meter',
            'unit_short_code' => 'Mtr',
            'unit_conversion_quantity' => 1.0,
            'quantity' => 10,
            'base_unit_price' => 50.0,
            'customer_unit_price' => 50.0,
            'line_subtotal' => 500.0,
            'line_total' => 500.0,
            'status' => 'pending_dispatch',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(OrderShowPage::class, ['orderNumber' => $this->order->order_number])
            ->set('selectedItemIds', [$item1->id, $item2->id])
            ->call('openBulkDispatchModal')
            ->set('bulkDispatchQuantities.' . $item1->id, 5)
            ->set('bulkDispatchQuantities.' . $item2->id, 4)
            ->set('dispatchNote', 'Bulk dispatch note')
            ->call('confirmBulkDispatch')
            ->assertHasNoErrors();

        $items = $this->order->fresh()->items;
        
        $dispatchedItems = $items->where('status', 'dispatched');
        $this->assertCount(2, $dispatchedItems);
        foreach ($dispatchedItems as $dispItem) {
            $this->assertEquals('Bulk dispatch note', $dispItem->dispatch_note);
        }
    }
}
