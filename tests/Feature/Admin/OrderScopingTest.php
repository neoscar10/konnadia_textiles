<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;
use App\Livewire\Admin\Admins\AdminIndexPage;
use App\Services\Order\AdminOrderService;

class OrderScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $customerUser;
    protected Customer $customer;
    protected Product $manufacturedProduct;
    protected Product $retailProduct;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Setup roles & permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'access orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage manufactured orders', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage retail orders', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $this->customerUser = User::factory()->create();
        $level = CustomerLevel::create([
            'name' => 'Level 1',
            'discount_percentage' => 0,
            'default_credit_limit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-SCOP-01',
            'customer_level_id' => $level->id,
            'company_name' => 'Scope Test Corp',
            'gst_number' => 'GST-SCOP-01',
            'contact_person' => 'Scope Tester',
            'mobile_number' => '9876543211',
            'email' => $this->customerUser->email,
            'credit_limit' => 10000,
            'outstanding_amount' => 0.0,
            'available_credit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->manufacturedProduct = Product::create([
            'title' => 'Manufactured Thread',
            'sku' => 'THR-MFG-01',
            'base_price' => 100.0,
            'product_type' => 'manufactured',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->retailProduct = Product::create([
            'title' => 'Retail Box',
            'sku' => 'BOX-RTL-01',
            'base_price' => 50.0,
            'product_type' => 'retail',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
    }

    public function test_validation_requires_order_scope_when_orders_access_granted(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminIndexPage::class)
            ->set('form.name', 'Scope Admin')
            ->set('form.email', 'scopeadmin@test.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('selectedPermissions', ['access orders'])
            ->call('save')
            ->assertHasErrors(['orderScope']);
    }

    public function test_can_create_admin_with_valid_order_scope(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(AdminIndexPage::class)
            ->set('form.name', 'Mfg Admin')
            ->set('form.email', 'mfgadmin@test.com')
            ->set('form.password', 'password123')
            ->set('form.password_confirmation', 'password123')
            ->set('selectedPermissions', ['access orders', 'manage manufactured orders'])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'mfgadmin@test.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasPermissionTo('access orders'));
        $this->assertTrue($user->hasPermissionTo('manage manufactured orders'));
    }

    public function test_mfg_admin_only_sees_manufactured_orders_and_items(): void
    {
        // Order 1: Manufactured only
        $orderMfg = Order::create([
            'order_number' => 'KT-ORD-MFG001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'subtotal' => 100.0,
            'gst_amount' => 12.0,
            'total_amount' => 112.0,
        ]);
        OrderItem::create([
            'order_id' => $orderMfg->id,
            'product_id' => $this->manufacturedProduct->id,
            'product_title' => $this->manufacturedProduct->title,
            'product_sku' => $this->manufacturedProduct->sku,
            'unit_name' => 'Meter',
            'quantity' => 1,
            'customer_unit_price' => 100.0,
            'line_subtotal' => 100.0,
            'gst_percentage' => 12.0,
            'gst_amount' => 12.0,
            'line_total' => 112.0,
        ]);

        // Order 2: Retail only
        $orderRtl = Order::create([
            'order_number' => 'KT-ORD-RTL001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'subtotal' => 50.0,
            'gst_amount' => 6.0,
            'total_amount' => 56.0,
        ]);
        OrderItem::create([
            'order_id' => $orderRtl->id,
            'product_id' => $this->retailProduct->id,
            'product_title' => $this->retailProduct->title,
            'product_sku' => $this->retailProduct->sku,
            'unit_name' => 'Box',
            'quantity' => 1,
            'customer_unit_price' => 50.0,
            'line_subtotal' => 50.0,
            'gst_percentage' => 12.0,
            'gst_amount' => 6.0,
            'line_total' => 56.0,
        ]);

        // Order 3: Mixed
        $orderMixed = Order::create([
            'order_number' => 'KT-ORD-MIX001',
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'status' => 'submitted',
            'checkout_method' => 'credit',
            'subtotal' => 150.0,
            'gst_amount' => 18.0,
            'total_amount' => 168.0,
        ]);
        OrderItem::create([
            'order_id' => $orderMixed->id,
            'product_id' => $this->manufacturedProduct->id,
            'product_title' => $this->manufacturedProduct->title,
            'product_sku' => $this->manufacturedProduct->sku,
            'unit_name' => 'Meter',
            'quantity' => 1,
            'customer_unit_price' => 100.0,
            'line_subtotal' => 100.0,
            'gst_percentage' => 12.0,
            'gst_amount' => 12.0,
            'line_total' => 112.0,
        ]);
        OrderItem::create([
            'order_id' => $orderMixed->id,
            'product_id' => $this->retailProduct->id,
            'product_title' => $this->retailProduct->title,
            'product_sku' => $this->retailProduct->sku,
            'unit_name' => 'Box',
            'quantity' => 1,
            'customer_unit_price' => 50.0,
            'line_subtotal' => 50.0,
            'gst_percentage' => 12.0,
            'gst_amount' => 6.0,
            'line_total' => 56.0,
        ]);

        // Create Mfg Admin User
        $mfgAdmin = User::factory()->create();
        $mfgAdmin->assignRole('admin');
        $mfgAdmin->givePermissionTo(['access orders', 'manage manufactured orders']);

        $this->actingAs($mfgAdmin);

        $service = app(AdminOrderService::class);
        
        // 1. Verify listOrders() returns only Order 1 and Order 3
        $ordersList = $service->listOrders();
        $this->assertCount(2, $ordersList);
        $orderNumbers = collect($ordersList->items())->pluck('order_number')->toArray();
        $this->assertContains('KT-ORD-MFG001', $orderNumbers);
        $this->assertContains('KT-ORD-MIX001', $orderNumbers);
        $this->assertNotContains('KT-ORD-RTL001', $orderNumbers);

        // 2. Verify formatAdminOrderCard scopes the total of Mixed order to Mfg only ($112 instead of $168)
        $mixedCard = collect($ordersList->items())->firstWhere('order_number', 'KT-ORD-MIX001');
        $this->assertEquals(112.0, $mixedCard['total_amount']);

        // 3. Verify detail view of Mixed order only contains Mfg item and displays scoped totals
        $details = $service->getOrderDetail($orderMixed);
        $this->assertCount(1, $details['items']);
        $this->assertEquals('THR-MFG-01', $details['items'][0]['product_sku']);
        $this->assertEquals(100.0, $details['subtotal']);
        $this->assertEquals(12.0, $details['gst_amount']);
        $this->assertEquals(112.0, $details['total_amount']);

        // 4. Verify detail view of Retail only order fails with ModelNotFoundException
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->getOrderDetail($orderRtl);
    }
}
