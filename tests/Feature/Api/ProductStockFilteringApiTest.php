<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Models\ProductCombination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductStockFilteringApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $inStockProduct;
    protected Product $outOfStockProduct;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $level = CustomerLevel::create([
            'name' => 'Standard Tier',
            'discount_percentage' => 0,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-TEST-001',
            'customer_level_id' => $level->id,
            'company_name' => 'Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Test Person',
            'mobile_number' => '9000000001',
            'email' => $this->user->email,
            'is_active' => true,
        ]);

        $this->inStockProduct = Product::create([
            'title' => 'In Stock Shirt',
            'sku' => 'SKU-IN-STOCK',
            'base_price' => 500,
            'stock_quantity' => 50,
            'is_active' => true,
        ]);

        $this->outOfStockProduct = Product::create([
            'title' => 'Out of Stock Pants',
            'sku' => 'SKU-OUT-OF-STOCK',
            'base_price' => 700,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
    }

    public function test_filters_in_stock_products_using_availability_in_stock()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?availability=in_stock');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->inStockProduct->id, $ids);
        $this->assertNotContains($this->outOfStockProduct->id, $ids);
    }

    public function test_filters_out_of_stock_products_using_availability_out_of_stock()
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?availability=out_of_stock');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->outOfStockProduct->id, $ids);
        $this->assertNotContains($this->inStockProduct->id, $ids);
    }

    public function test_filters_using_stock_status_alias()
    {
        // Test stock_status=instock
        $response1 = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?stock_status=instock');

        $response1->assertStatus(200);
        $ids1 = collect($response1->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->inStockProduct->id, $ids1);
        $this->assertNotContains($this->outOfStockProduct->id, $ids1);

        // Test stock_status=outofstock
        $response2 = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?stock_status=outofstock');

        $response2->assertStatus(200);
        $ids2 = collect($response2->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->outOfStockProduct->id, $ids2);
        $this->assertNotContains($this->inStockProduct->id, $ids2);
    }

    public function test_filters_using_in_stock_boolean_aliases()
    {
        // in_stock=true or in_stock=1
        $responseTrue = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?in_stock=true');

        $responseTrue->assertStatus(200);
        $idsTrue = collect($responseTrue->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->inStockProduct->id, $idsTrue);
        $this->assertNotContains($this->outOfStockProduct->id, $idsTrue);

        // in_stock=false or in_stock=0
        $responseFalse = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/products?in_stock=false');

        $responseFalse->assertStatus(200);
        $idsFalse = collect($responseFalse->json('data'))->pluck('id')->toArray();
        $this->assertContains($this->outOfStockProduct->id, $idsFalse);
        $this->assertNotContains($this->inStockProduct->id, $idsFalse);
    }
}
