<?php

namespace Tests\Feature;

use App\Models\AdminNotificationContact;
use App\Models\User;
use App\Models\Order;
use App\Models\ProductTransfer;
use App\Models\RetailShop;
use App\Services\Notification\AdminWhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminWhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::create(['name' => 'super_admin']);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_can_manage_admin_notification_contacts_via_livewire()
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\NotificationContacts\AdminNotificationContactsIndex::class)
            ->set('name', 'Operations Manager')
            ->set('phone_number', '+919911041964')
            ->set('is_active', true)
            ->set('notify_new_orders', true)
            ->set('notify_goods_transfers', true)
            ->set('notify_order_dispatches', true)
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('admin_notification_contacts', [
            'name' => 'Operations Manager',
            'phone_number' => '+919911041964',
            'notify_new_orders' => true,
        ]);
    }

    public function test_admin_whatsapp_notification_service_dispatches_alerts()
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messaging_product' => 'whatsapp', 'messages' => [['id' => 'wamid.123']]], 200),
        ]);

        AdminNotificationContact::create([
            'name' => 'Main Admin',
            'phone_number' => '+919911041964',
            'is_active' => true,
            'notify_new_orders' => true,
            'notify_goods_transfers' => true,
            'notify_order_dispatches' => true,
        ]);

        $service = app(AdminWhatsAppNotificationService::class);

        // Test New Order Alert
        $order = Order::factory()->create([
            'order_number' => 'KT-ORD-TEST-01',
            'total_amount' => 5000.00,
        ]);

        $resOrder = $service->notifyNewOrder($order);
        $this->assertTrue($resOrder['success']);
        $this->assertEquals(1, $resOrder['recipients_count']);

        // Test Goods Transfer Alert
        $shop = RetailShop::create([
            'name' => 'Surat Branch',
            'shop_code' => 'SURAT-01',
            'is_active' => true,
        ]);

        $transfer = ProductTransfer::create([
            'transfer_number' => 'TRF-TEST-001',
            'retail_shop_id' => $shop->id,
            'created_by' => $this->admin->id,
            'status' => 'completed',
            'transfer_date' => now()->toDateString(),
            'total_items' => 2,
            'total_quantity_base_units' => 50,
        ]);

        $resTransfer = $service->notifyGoodsTransfer($transfer);
        $this->assertTrue($resTransfer['success']);

        // Test Order Dispatch Alert
        $resDispatch = $service->notifyOrderDispatch($order, ['tracking_number' => 'BD123456']);
        $this->assertTrue($resDispatch['success']);
    }
}
