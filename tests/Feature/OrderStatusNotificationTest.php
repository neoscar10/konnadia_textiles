<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class OrderStatusNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customerUser;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');

        $this->customerUser = User::factory()->create(['mobile_number' => '9876543210']);
        $this->customerUser->assignRole('customer');

        $level = CustomerLevel::create([
            'name' => 'Silver Level',
            'discount_percentage' => 5,
            'is_active' => true,
            'gst_percentage' => 12,
            'hsn_code' => '6205',
        ]);

        $customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-100',
            'customer_level_id' => $level->id,
            'company_name' => 'Test Trading Co',
            'gst_number' => '24ABCDE1234F1Z5',
            'contact_person' => 'Test Person',
            'mobile_number' => '9876543210',
            'email' => 'test@trading.com',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'user_id' => $this->customerUser->id,
            'customer_id' => $customer->id,
            'order_number' => 'KT-ORD-TEST-001',
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'subtotal' => 1000,
            'gst_amount' => 120,
            'total_amount' => 1120,
        ]);

        UserDeviceToken::create([
            'user_id' => $this->customerUser->id,
            'device_token' => 'test_fcm_device_token_123',
            'platform' => 'android',
        ]);
    }

    public function test_order_status_transition_triggers_notifications(): void
    {
        Notification::fake();

        $statusService = app(OrderStatusService::class);
        $statusService->transition($this->order, 'under_review', $this->admin, 'Reviewing order details');

        // Verify DB notification sent to customer
        Notification::assertSentTo(
            $this->customerUser,
            OrderStatusUpdatedNotification::class,
            function ($notification) {
                return $notification->toStatus === 'under_review' && $notification->order->id === $this->order->id;
            }
        );
    }
}
