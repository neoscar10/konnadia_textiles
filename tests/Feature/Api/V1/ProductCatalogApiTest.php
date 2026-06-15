<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\ProductCombination;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ProductCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected User $adminUser;
    protected Customer $customer;
    protected CustomerLevel $customerLevel;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create API roles
        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        // Set up customer level
        $this->customerLevel = CustomerLevel::create([
            'name' => 'Silver Tier',
            'discount_percentage' => 15.00,
            'is_active' => true,
        ]);

        // Customer User
        $this->customerUser = User::factory()->create();
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-API-001',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'API Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'API Contact',
            'mobile_number' => '9000000001',
            'email' => $this->customerUser->email,
            'is_active' => true,
        ]);

        // Admin User
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('super_admin');
    }

    /**
     * Test guests cannot access catalog endpoints.
     */
    public function test_guest_is_unauthorized(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
        $this->getJson('/api/v1/products/filters')->assertStatus(401);
        $this->getJson('/api/v1/products/1')->assertStatus(401);
        $this->getJson('/api/v1/products/1/related')->assertStatus(401);
    }

    /**
     * Test admin users or users without active customer profiles are forbidden.
     */
    public function test_admin_is_forbidden_from_product_catalog(): void
    {
        $response = $this->actingAs($this->adminUser, 'api')->getJson('/api/v1/products');
        
        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Customer profile not found or inactive for this account.');
    }

    /**
     * Test inactive customer is forbidden.
     */
    public function test_inactive_customer_is_forbidden(): void
    {
        $this->customer->update(['is_active' => false]);

        $response = $this->actingAs($this->customerUser, 'api')->getJson('/api/v1/products');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /**
     * Test basic active products listing.
     */
    public function test_active_products_listing(): void
    {
        // Active Product
        $p1 = Product::create([
            'title' => 'Silk Saree',
            'sku' => 'SLK-001',
            'base_price' => 5000.00,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        // Inactive Product
        $p2 = Product::create([
            'title' => 'Cotton Fabric',
            'sku' => 'COT-001',
            'base_price' => 120.00,
            'is_active' => false,
            'stock_quantity' => 20,
        ]);

        $response = $this->actingAs($this->customerUser, 'api')->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p1->id)
            ->assertJsonPath('data.0.sku', 'SLK-001');
    }

    /**
     * Test category filtering.
     */
    public function test_category_filters(): void
    {
        $category = Category::create([
            'name' => 'Premium Ethnic',
            'slug' => 'premium-ethnic',
            'is_active' => true,
        ]);

        $p1 = Product::create([
            'title' => 'Banarasi Saree',
            'sku' => 'BAN-001',
            'base_price' => 8000.00,
            'is_active' => true,
            'stock_quantity' => 5,
        ]);
        $p1->categories()->attach($category->id);

        $p2 = Product::create([
            'title' => 'Casual Jeans',
            'sku' => 'JNS-002',
            'base_price' => 1500.00,
            'is_active' => true,
            'stock_quantity' => 15,
        ]);

        // Filter by category_id
        $resId = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products?category_id=' . $category->id);
        $resId->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p1->id);

        // Filter by category_slug
        $resSlug = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products?category_slug=premium-ethnic');
        $resSlug->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $p1->id);
    }

    /**
     * Test price, search, and availability filtering.
     */
    public function test_advanced_listings_filtering(): void
    {
        $p1 = Product::create([
            'title' => 'Luxury Kurta',
            'sku' => 'KUR-L1',
            'base_price' => 3000.00,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $p2 = Product::create([
            'title' => 'Basic Kurta',
            'sku' => 'KUR-B1',
            'base_price' => 800.00,
            'is_active' => true,
            'stock_quantity' => 0, // Out of stock
        ]);

        // Search test
        $resSearch = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products?search=Luxury');
        $resSearch->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'KUR-L1');

        // Price max test
        $resPrice = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products?price_max=1000');
        $resPrice->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'KUR-B1');

        // In stock availability filter
        $resStock = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products?availability=in_stock');
        $resStock->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.sku', 'KUR-L1');
    }

    /**
     * Test product details by ID and by Slug.
     */
    public function test_product_detail_retrieval(): void
    {
        $product = Product::create([
            'title' => 'Printed Lehenga Choli',
            'sku' => 'LHG-009',
            'base_price' => 12000.00,
            'description' => 'A **premium lehenga** with custom printing.',
            'is_active' => true,
            'stock_quantity' => 4,
        ]);

        // Create base unit
        ProductUnit::create([
            'product_id' => $product->id,
            'name' => 'Piece',
            'short_code' => 'PCS',
            'level' => 1,
            'conversion_to_base' => 1.00,
            'multiplier' => 1.00,
        ]);

        // Detail by ID
        $resId = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products/' . $product->id);
        
        $resId->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.slug', 'printed-lehenga-choli')
            ->assertJsonPath('data.description.markdown', 'A **premium lehenga** with custom printing.')
            ->assertJsonPath('data.pricing.base_price', 12000)
            // Silver tier gets 15% discount: 12000 * 0.85 = 10200
            ->assertJsonPath('data.pricing.customer_price', 10200);

        // Detail by Slug
        $resSlug = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/products/printed-lehenga-choli');
        
        $resSlug->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    /**
     * Test related products endpoint.
     */
    public function test_related_products(): void
    {
        $category = Category::create([
            'name' => 'Kurtis',
            'slug' => 'kurtis',
            'is_active' => true,
        ]);

        $p1 = Product::create([
            'title' => 'Anarkali Kurti',
            'sku' => 'KUR-AN',
            'base_price' => 2500.00,
            'is_active' => true,
            'stock_quantity' => 8,
        ]);
        $p1->categories()->attach($category->id);

        $p2 = Product::create([
            'title' => 'Georgette Kurti',
            'sku' => 'KUR-GE',
            'base_price' => 1800.00,
            'is_active' => true,
            'stock_quantity' => 12,
        ]);
        $p2->categories()->attach($category->id);

        $p3 = Product::create([
            'title' => 'Silk Kurti',
            'sku' => 'KUR-SK',
            'base_price' => 3500.00,
            'is_active' => true,
            'stock_quantity' => 5,
        ]);
        $p3->categories()->attach($category->id);

        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson("/api/v1/products/{$p1->id}/related?limit=2");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test filters metadata endpoint.
     */
    public function test_filters_metadata(): void
    {
        Category::create([
            'name' => 'Fabrics',
            'slug' => 'fabrics',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->customerUser, 'api')->getJson('/api/v1/products/filters');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'categories',
                    'availability',
                    'sort',
                    'price_range' => ['min', 'max', 'currency']
                ]
            ]);
    }
}
