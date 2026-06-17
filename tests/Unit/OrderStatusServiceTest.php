<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class OrderStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderStatusService $statusService;
    protected Order $order;
    protected User $adminUser;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statusService = new OrderStatusService();

        // Create roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');

        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        // Create a basic customer profile
        $level = \App\Models\CustomerLevel::create([
            'name' => 'Gold Level',
            'discount_percentage' => 10,
            'default_credit_limit' => 100000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
        $customer = \App\Models\Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-TEST-001',
            'customer_level_id' => $level->id,
            'company_name' => 'Testing Inc',
            'gst_number' => 'GSTIN-123',
            'contact_person' => 'Tester',
            'mobile_number' => '9999999999',
            'email' => $this->customerUser->email,
            'credit_limit' => 100000,
            'outstanding_amount' => 0.0,
            'available_credit' => 100000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->order = Order::create([
            'order_number' => 'KT-ORD-111111',
            'user_id' => $this->customerUser->id,
            'customer_id' => $customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'payment_status' => 'not_required',
            'total_amount' => 5000.0,
        ]);
    }

    public function test_valid_status_transitions(): void
    {
        // submitted -> under_review is allowed
        $this->assertTrue($this->statusService->canTransition($this->order, 'under_review'));

        // submitted -> approved is allowed
        $this->assertTrue($this->statusService->canTransition($this->order, 'approved'));

        // submitted -> rejected is allowed
        $this->assertTrue($this->statusService->canTransition($this->order, 'rejected'));
    }

    public function test_invalid_status_transitions(): void
    {
        // submitted -> dispatched is not allowed (must be approved first)
        $this->assertFalse($this->statusService->canTransition($this->order, 'dispatched'));

        // submitted -> cancelled is not allowed
        $this->assertFalse($this->statusService->canTransition($this->order, 'cancelled'));
    }

    public function test_payment_verification_required_before_approval(): void
    {
        $this->order->update([
            'status' => 'pending_payment_verification',
            'checkout_method' => 'manual_payment',
            'payment_status' => 'pending_verification',
        ]);

        // Cannot transition directly to approved when payment_status is pending_verification
        $this->assertFalse($this->statusService->canTransition($this->order, 'approved'));

        // Once verified, can transition to approved
        $this->order->update(['payment_status' => 'verified']);
        $this->assertTrue($this->statusService->canTransition($this->order, 'approved'));
    }

    public function test_dispatched_and_cancelled_cannot_transition_further(): void
    {
        $this->order->update(['status' => 'dispatched']);
        $this->assertFalse($this->statusService->canTransition($this->order, 'approved'));
        $this->assertFalse($this->statusService->canTransition($this->order, 'cancelled'));

        $this->order->update(['status' => 'cancelled']);
        $this->assertFalse($this->statusService->canTransition($this->order, 'approved'));
        $this->assertFalse($this->statusService->canTransition($this->order, 'dispatched'));
    }

    public function test_allowed_actions_for_actors(): void
    {
        // Admin allowed actions on submitted order
        $adminActions = $this->statusService->getAllowedActions($this->order, $this->adminUser);
        $this->assertContains('under_review', $adminActions);
        $this->assertContains('approve', $adminActions);
        $this->assertContains('reject', $adminActions);

        // Customer allowed actions on submitted order (none by default)
        $customerActions = $this->statusService->getAllowedActions($this->order, $this->customerUser);
        $this->assertEmpty($customerActions);
    }
}
