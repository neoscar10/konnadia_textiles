<?php

namespace Tests\Feature\Api\V1;

use App\Models\Order;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomerOrderTrackingApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer']);

        $this->user = User::factory()->create([
            'is_active' => true,
        ]);
        $this->user->assignRole('customer');

        $level = \App\Models\CustomerLevel::create([
            'name' => 'Test Level',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_level_id' => $level->id,
            'customer_number' => 'CUST-0001',
            'company_name' => 'Test Company',
            'email' => $this->user->email,
            'mobile_number' => '9876543210',
            'credit_limit' => 10000.00,
            'available_credit' => 10000.00,
            'is_active' => true,
            'billing_address' => 'Test Address',
            'gst_number' => '12ABCDE3456F7GH',
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'contact_person' => 'John Doe',
        ]);

        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function createOrder(int $userId, int $customerId, string $status = 'submitted', float $total = 5000)
    {
        return Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'user_id' => $userId,
            'customer_id' => $customerId,
            'status' => $status,
            'checkout_method' => 'credit',
            'payment_status' => 'not_required',
            'subtotal' => $total,
            'gst_amount' => 0,
            'total_amount' => $total,
            'submitted_at' => now(),
        ]);
    }

    public function test_customer_can_list_their_own_orders()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->createOrder($this->user->id, $this->user->customer->id);
        }

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'order_number', 'status', 'summary', 'items_count', 'first_item'
                    ]
                ]
            ]);
            
        $this->assertCount(3, $response->json('data'));
    }

    public function test_customer_cannot_see_other_peoples_orders()
    {
        $otherUser = User::factory()->create();
        $level = \App\Models\CustomerLevel::firstOrCreate([
            'name' => 'Test Level 2',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);
        Customer::create([
            'user_id' => $otherUser->id,
            'customer_level_id' => $level->id,
            'customer_number' => 'CUST-0002',
            'company_name' => 'Other Company',
            'email' => $otherUser->email,
            'mobile_number' => '9876543211',
            'credit_limit' => 10000.00,
            'available_credit' => 10000.00,
            'is_active' => true,
            'billing_address' => 'Test Address',
            'gst_number' => '12ABCDE3456F7GH',
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'contact_person' => 'Jane Doe',
        ]);
        $this->createOrder($otherUser->id, $otherUser->customer->id);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/orders');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_customer_can_view_order_details()
    {
        $order = $this->createOrder($this->user->id, $this->user->customer->id, 'approved');

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.important_message.type', 'success');
    }

    public function test_customer_can_view_order_summary()
    {
        $this->createOrder($this->user->id, $this->user->customer->id, 'approved', 1000);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/orders/summary');

        $response->assertStatus(200)
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonPath('data.approved_orders', 1)
            ->assertJsonPath('data.total_order_value', 1000);
    }
}
