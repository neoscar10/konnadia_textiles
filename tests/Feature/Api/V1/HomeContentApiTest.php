<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class HomeContentApiTest extends TestCase
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

        Role::create(['name' => 'customer', 'guard_name' => 'api']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'api']);
        Role::create(['name' => 'customer', 'guard_name' => 'web']);
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Gold Tier',
            'discount_percentage' => 15.00,
            'default_credit_limit' => 100000.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customerUser = User::factory()->create(['is_active' => true]);
        $this->customerUser->assignRole('customer');

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'customer_number' => 'CUST-DB-99',
            'customer_level_id' => $this->customerLevel->id,
            'company_name' => 'API Home Test Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'API Contact',
            'mobile_number' => '9000000010',
            'email' => $this->customerUser->email,
            'credit_limit' => 200000.00,
            'outstanding_amount' => 50000.00,
            'available_credit' => 150000.00,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->assignRole('super_admin');
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $this->getJson('/api/v1/home')->assertStatus(401);
    }

    public function test_admin_gets_403_on_customer_home(): void
    {
        $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/home')
            ->assertStatus(403);
    }

    public function test_active_customer_gets_dynamic_home_content(): void
    {
        // 1. Create a Banner section
        $bannerSection = HomeContentSection::create([
            'type' => 'banner',
            'title' => 'Big Discount Banners',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HomeContentItem::create([
            'home_content_section_id' => $bannerSection->id,
            'title' => 'Summer Banner 1',
            'cta_label' => 'Shop Now',
            'image_path' => 'banners/summer1.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 2. Create a Product section
        $product = Product::factory()->create([
            'title' => 'Silk Saree Premium',
            'base_price' => 5000.00,
            'is_active' => true,
        ]);

        $product->customerLevelPrices()->create([
            'customer_level_id' => $this->customerLevel->id,
            'price' => 4200.00,
        ]);

        $productSection = HomeContentSection::create([
            'type' => 'product_slider',
            'title' => 'Hot Deals',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        HomeContentItem::create([
            'home_content_section_id' => $productSection->id,
            'product_id' => $product->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Make API request
        $response = $this->actingAs($this->customerUser, 'api')
            ->getJson('/api/v1/home');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'subtitle',
                        'sort_order',
                        'display_style',
                        'items' => [
                            '*' => [
                                'id',
                                'type',
                            ]
                        ]
                    ]
                ]
            ]);

        // Check if level price is used and absolute URL resolved
        $data = $response->json('data');
        
        $this->assertCount(2, $data);

        // Check Banner section details
        $this->assertEquals('banner', $data[0]['type']);
        $this->assertEquals('Big Discount Banners', $data[0]['title']);
        $this->assertStringContainsString('http', $data[0]['items'][0]['image_url']);
        $this->assertStringContainsString('banners/summer1.png', $data[0]['items'][0]['image_url']);

        // Check Product section details
        $this->assertEquals('product_slider', $data[1]['type']);
        $this->assertEquals('Hot Deals', $data[1]['title']);
        $productCard = $data[1]['items'][0]['product'];
        $this->assertEquals('Silk Saree Premium', $productCard['title']);
        // Check B2B pricing reflects customer level price
        $this->assertEquals(4200.00, $productCard['price']);
    }
}
