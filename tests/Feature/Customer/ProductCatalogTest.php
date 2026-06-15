<?php

namespace Tests\Feature\Customer;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductCustomerLevelPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerLevel $level;

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
        ]);
    }

    public function test_product_listing_page_loads_for_customer(): void
    {
        $response = $this->actingAs($this->user)->get('/portal/products');
        $response->assertStatus(200);
    }

    public function test_only_active_products_are_shown(): void
    {
        Product::create([
            'title' => 'Active Product',
            'sku' => 'ACT-001',
            'base_price' => 100,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        Product::create([
            'title' => 'Inactive Product',
            'sku' => 'INA-002',
            'base_price' => 200,
            'is_active' => false,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->get('/portal/products');
        $response->assertSee('Active Product');
        $response->assertDontSee('Inactive Product');
    }

    public function test_listing_uses_customer_level_discount(): void
    {
        $product = Product::create([
            'title' => 'Discounted Product',
            'sku' => 'DIS-001',
            'base_price' => 1000,
            'is_active' => true,
            'stock_quantity' => 15,
        ]);

        // Gold Level gets 10% discount, so customer price should be 900
        $response = $this->actingAs($this->user)->get('/portal/products');
        $response->assertSee('900');
    }

    public function test_search_filters_products(): void
    {
        Product::create([
            'title' => 'Blue Shirt',
            'sku' => 'SHI-001',
            'base_price' => 100,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        Product::create([
            'title' => 'Red Pants',
            'sku' => 'PAN-002',
            'base_price' => 100,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->get('/portal/products?search=Blue');
        $response->assertSee('Blue Shirt');
        $response->assertDontSee('Red Pants');
    }
}
