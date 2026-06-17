<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductUnit;
use App\Models\ProductVariationGroup;
use App\Models\ProductVariationValue;
use App\Models\ProductCombination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerLevel $level;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        Role::create(['name' => 'customer']);

        $this->user = User::factory()->create();
        $this->user->assignRole('customer');

        $this->level = CustomerLevel::create([
            'name' => 'Gold Distributor',
            'discount_percentage' => 10.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-0001',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'Test Person',
            'mobile_number' => '9876543210',
            'email' => $this->user->email,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        // Create standard product
        $this->product = Product::create([
            'title' => 'Formal Cotton Shirt',
            'sku' => 'FCS-123',
            'base_price' => 1000.00,
            'description' => 'A very fine cotton shirt.',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 20,
        ]);

        // Create unit
        ProductUnit::create([
            'product_id' => $this->product->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'pcs',
            'conversion_to_base' => 1.0,
        ]);
    }

    public function test_product_detail_page_loads_real_product(): void
    {
        $response = $this->actingAs($this->user)->get('/portal/products/formal-cotton-shirt');
        $response->assertStatus(200);
        $response->assertSee('Formal Cotton Shirt');
        $response->assertSee('FCS-123');
    }

    public function test_out_of_stock_product_shows_status(): void
    {
        $outOfStockProduct = Product::create([
            'title' => 'Sold Out Pants',
            'sku' => 'SOP-999',
            'base_price' => 500,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'stock_quantity' => 0,
        ]);

        ProductUnit::create([
            'product_id' => $outOfStockProduct->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'pcs',
            'conversion_to_base' => 1.0,
        ]);

        $response = $this->actingAs($this->user)->get('/portal/products/sold-out-pants');
        $response->assertStatus(200);
        $response->assertSee('Out of Stock');
    }
}
