<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderDispatch;
use App\Models\ProductTransfer;
use App\Models\RetailShop;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiDocumentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        Permission::firstOrCreate(['name' => 'access orders']);
        Permission::firstOrCreate(['name' => 'access product-transfers']);
        $role->givePermissionTo(['access orders', 'access product-transfers']);

        $this->admin = User::factory()->create(['email' => 'doc_admin@konnadia.com']);
        $this->admin->assignRole('super_admin');

        $this->jwtToken = JWTAuth::fromUser($this->admin);
    }

    public function test_order_dispatch_document_can_be_downloaded_via_bearer_header(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'order_number' => 'KT-ORD-999001',
            'user_id' => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => 'Test Product',
            'product_sku' => 'SKU-001',
            'unit_name' => 'Pcs',
            'unit_short_code' => 'pcs',
            'quantity' => 5,
            'customer_unit_price' => 100,
            'line_subtotal' => 500,
            'line_total' => 500,
            'dispatch_number' => 'DISP-KT-ORD-999001-1',
            'status' => 'dispatched',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->jwtToken,
        ])->get('/api/v1/admin/orders/dispatch/DISP-KT-ORD-999001-1/download');

        $response->assertStatus(200);
    }

    public function test_order_dispatch_document_can_be_downloaded_via_query_token(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create([
            'order_number' => 'KT-ORD-999002',
            'user_id' => $this->admin->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_title' => 'Test Product',
            'product_sku' => 'SKU-002',
            'unit_name' => 'Pcs',
            'unit_short_code' => 'pcs',
            'quantity' => 10,
            'customer_unit_price' => 100,
            'line_subtotal' => 1000,
            'line_total' => 1000,
            'dispatch_number' => 'DISP-KT-ORD-999002-1',
            'status' => 'dispatched',
        ]);

        // Access directly via query string ?token=... (simulating browser URL bar or Flutter downloader)
        $response = $this->get('/api/v1/admin/orders/dispatch/DISP-KT-ORD-999002-1/download?token=' . $this->jwtToken);

        $response->assertStatus(200);
    }

    public function test_product_transfer_document_can_be_downloaded_via_query_token(): void
    {
        $shop = RetailShop::create([
            'shop_code' => 'SHP-001',
            'name' => 'Downtown Retail Store',
            'city' => 'Mumbai',
            'is_active' => true,
        ]);

        $transfer = ProductTransfer::create([
            'transfer_number' => 'TRF-2026-0001',
            'retail_shop_id' => $shop->id,
            'created_by' => $this->admin->id,
            'transferred_by_user_id' => $this->admin->id,
            'status' => 'completed',
            'total_items' => 10,
        ]);

        $response = $this->get("/api/v1/admin/product-transfers/{$transfer->id}/download?token={$this->jwtToken}");

        $response->assertStatus(200);
    }
}
