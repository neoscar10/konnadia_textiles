<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminOrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected User $customerUser;
    protected Customer $customerProfile;
    protected Order $order;
    protected OrderItem $orderItem;
    protected Product $product;
    protected ProductUnit $setUnit;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permOrders = Permission::firstOrCreate(['name' => 'access orders', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permOrders]);

        $this->superAdmin = User::factory()->create(['email' => 'super_order@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_order@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $level = CustomerLevel::create([
            'name' => 'Wholesale Tier 1',
            'discount_percentage' => 10.0,
            'default_credit_limit' => 100000,
            'is_active' => true,
        ]);

        $this->customerUser = User::factory()->create(['email' => 'customer_ord@konnadia.com']);
        $this->customerProfile = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'KT-CUST-9001',
            'company_name' => 'Surat Textiles Ltd',
            'contact_person' => 'Vikram Patel',
            'mobile_number' => '+91 9988776655',
            'email' => 'vikram@surattextiles.com',
            'gst_number' => '24AAACC1234H1Z0',
            'billing_address' => '100 Ring Road, Surat, Gujarat',
            'customer_level_id' => $level->id,
            'credit_limit' => 100000,
            'available_credit' => 100000,
            'status' => 'approved',
        ]);

        $this->product = Product::create([
            'title' => 'Designer Cotton Set',
            'sku' => 'KT-DSET-01',
            'base_price' => 2000.00,
            'description' => 'Designer set of 4 pieces',
            'stock_quantity' => 50,
            'is_active' => true,
            'product_type' => 'retail',
        ]);

        $this->setUnit = ProductUnit::create([
            'product_id' => $this->product->id,
            'level' => 2,
            'name' => 'Set (4 Pcs)',
            'short_code' => 'set',
            'conversion_to_base' => 4.0,
        ]);

        $this->order = Order::create([
            'order_number' => 'KT-ORD-90001',
            'customer_id' => $this->customerProfile->id,
            'user_id' => $this->customerUser->id,
            'checkout_method' => 'credit',
            'status' => 'submitted',
            'payment_status' => 'pending',
            'credit_status' => 'approved',
            'subtotal' => 4000.00,
            'gst_amount' => 480.00,
            'total_amount' => 4480.00,
            'submitted_at' => now(),
        ]);

        $this->orderItem = OrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_unit_id' => $this->setUnit->id,
            'product_title' => 'Designer Cotton Set',
            'product_sku' => 'KT-DSET-01',
            'unit_name' => 'Set (4 Pcs)',
            'unit_short_code' => 'set',
            'unit_conversion_quantity' => 4.0,
            'quantity' => 2.0, // 2 sets = 8 pieces
            'quantity_lvl1' => 8,
            'quantity_lvl2' => 2.0,
            'customer_unit_price' => 2000.00,
            'gst_percentage' => 12.0,
            'gst_amount' => 480.00,
            'line_subtotal' => 4000.00,
            'line_total' => 4480.00,
            'status' => 'pending_dispatch',
        ]);
    }

    public function test_guest_cannot_access_order_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/orders');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_order_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/admin/orders');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_super_admin_can_fetch_order_stats_listing_and_details(): void
    {
        $statsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/orders/stats');

        $statsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_orders', 1);

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/orders?search=90001');

        $listResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/orders/' . $this->order->order_number);

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_number', 'KT-ORD-90001');
    }

    public function test_super_admin_can_approve_order(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/orders/' . $this->order->id . '/approve', [
                'admin_comment' => 'Approved by manager',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_super_admin_can_perform_fractional_item_dispatch(): void
    {
        // Approve first
        $this->order->update(['status' => 'approved']);

        // Perform fractional dispatch of 0.5 sets (2 pieces out of 2 sets)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/orders/items/' . $this->orderItem->id . '/dispatch', [
                'quantity' => 0.5,
                'note' => 'Partial dispatch of 0.5 sets (2 pieces)',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.dispatched_quantity', 0.5);

        // Verify original item was updated to 0.5 and marked dispatched
        $this->assertDatabaseHas('order_items', [
            'id' => $this->orderItem->id,
            'quantity' => 0.5,
            'status' => 'dispatched',
        ]);

        // Verify split item was created for remaining 1.5 sets with status pending_dispatch
        $this->assertDatabaseHas('order_items', [
            'order_id' => $this->order->id,
            'quantity' => 1.5,
            'status' => 'pending_dispatch',
        ]);
    }
}
