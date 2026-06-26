<?php

namespace Tests\Feature\Customer;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use App\Livewire\Customer\DashboardPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class HomeContentRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected CustomerLevel $level;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('customer');

        $this->level = CustomerLevel::create([
            'name' => 'Silver Level',
            'discount_percentage' => 10.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'CUST-1000',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Test Textiles Corp',
            'gst_number' => '24AAAAR8302F1Z4',
            'contact_person' => 'John Doe',
            'mobile_number' => '9876543210',
            'email' => $this->user->email,
            'credit_limit' => 50000.00,
            'outstanding_amount' => 10000.00,
            'available_credit' => 40000.00,
            'overdue_amount' => 0.00,
            'is_active' => true,
        ]);
    }

    public function test_portal_dashboard_renders_dynamic_home_content_when_configured()
    {
        $this->actingAs($this->user);

        // Create a dynamic Banner section
        $section = HomeContentSection::create([
            'type' => 'banner',
            'title' => 'Dynamic Summer Sale',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $item = HomeContentItem::create([
            'home_content_section_id' => $section->id,
            'title' => 'Exclusive Summer Offer',
            'subtitle' => 'Check our brand new textile collection',
            'cta_label' => 'Explore',
            'image_path' => 'banners/summer.jpg',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(DashboardPage::class)
            ->assertSee('Dynamic Summer Sale')
            ->assertSee('Exclusive Summer Offer');
    }

    public function test_portal_dashboard_renders_product_slider_with_b2b_pricing()
    {
        $this->actingAs($this->user);

        // Create product
        $product = Product::factory()->create([
            'title' => 'B2B Cotton Fabric',
            'base_price' => 1000.00,
            'is_active' => true,
        ]);

        // Add custom price for level
        $product->customerLevelPrices()->create([
            'customer_level_id' => $this->level->id,
            'price' => 850.00,
        ]);

        // Create product slider section
        $section = HomeContentSection::create([
            'type' => 'product_slider',
            'title' => 'Exclusive Fabric Picks',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $item = HomeContentItem::create([
            'home_content_section_id' => $section->id,
            'product_id' => $product->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::test(DashboardPage::class)
            ->assertSee('Exclusive Fabric Picks')
            ->assertSee('B2B Cotton Fabric')
            ->assertSee('₹850.00') // level price
            ->assertDontSee('₹1000.00'); // should not see base price
    }
}
