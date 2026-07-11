<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\CustomerLevel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Livewire\Admin\Products\ProductIndexPage;
use App\Livewire\Admin\Products\ProductShowPage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $user;
    private Category $category;
    private CustomerLevel $customerLevel;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic permissions and roles
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($customerRole);

        // Seed basic dependencies
        $this->category = Category::create([
            'name' => "Men's Wear",
            'slug' => 'mens-wear',
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            'default_product_config' => [
                'hsn_code' => '6205',
                'gst_percentage' => 12.0,
                'minimum_order_quantity' => 1,
                'product_type' => 'retail',
                'base_price' => 1000.00,
                'pricingOverrides' => [],
                'units' => [
                    'level1_name' => 'Piece',
                    'level1_code' => 'pcs',
                    'level2_name' => '',
                    'level2_code' => '',
                    'level2_conversion' => '',
                ],
            ],
        ]);

        $this->customerLevel = CustomerLevel::create([
            'name' => 'Wholesale Distributor',
            'discount_percentage' => 10.00,
            'default_credit_limit' => 500000.00,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);
    }

    public function test_guest_cannot_access_products_page()
    {
        $response = $this->get('/admin/products');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_products_page()
    {
        $response = $this->actingAs($this->user)->get('/admin/products');
        $response->assertRedirect(route('home'));
    }

    public function test_super_admin_can_access_products_page()
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/products');
        $response->assertSuccessful();
    }

    public function test_requires_title_and_description()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->set('basicInfo.title', '')
            ->set('basicInfo.description', '')
            ->call('save')
            ->assertHasErrors([
                'basicInfo.title' => 'required',
                'basicInfo.description' => 'required'
            ]);
    }

    public function test_category_defaults_requires_base_price_and_gst()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->set('currentCategoryId', $this->category->id)
            ->set('categoryDefaults.base_price', '')
            ->set('categoryDefaults.gst_percentage', '')
            ->call('saveCategoryDefaults')
            ->assertHasErrors([
                'categoryDefaults.base_price' => 'required',
                'categoryDefaults.gst_percentage' => 'required'
            ]);
    }

    public function test_requires_at_least_one_category()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->set('basicInfo.title', 'Linen Shirt')
            ->set('basicInfo.description', 'Nice linen shirt description')
            ->set('basicInfo.gst_percentage', 12.0)
            ->set('selectedCategoryIds', [])
            ->call('save')
            ->assertHasErrors(['selectedCategoryIds']);
    }

    public function test_can_create_non_variant_product()
    {
        $this->category->update([
            'default_product_config' => array_merge($this->category->default_product_config, ['base_price' => 999.00])
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->call('selectLeafForAddProduct', $this->category->id)
            ->set('basicInfo.title', 'Classic Chinos')
            ->set('basicInfo.description', 'Soft cotton fabric')
            ->set('nonVariantStock', 50)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'title' => 'Classic Chinos',
            'base_price' => 999.00,
            'sku' => 'KT-P-0001',
            'stock_quantity' => 50,
        ]);

        $product = Product::where('sku', 'KT-P-0001')->first();
        $this->assertTrue($product->categories->contains($this->category->id));
        $this->assertDatabaseHas('product_units', [
            'product_id' => $product->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'pcs',
        ]);
    }

    public function test_can_create_product_with_variations_and_combinations()
    {
        $groupsPayload = [
            [
                'name' => 'Size',
                'display_type' => 'text',
                'values' => [
                    ['value' => 'M', 'color_hex' => '', 'is_default' => true],
                    ['value' => 'L', 'color_hex' => '', 'is_default' => false],
                ]
            ],
            [
                'name' => 'Color',
                'display_type' => 'color',
                'values' => [
                    ['value' => 'Red', 'color_hex' => '#ff0000', 'is_default' => true],
                ]
            ]
        ];

        $combinationsPayload = [
            [
                'combination_values' => ['Size' => 'M', 'Color' => 'Red'],
                'sku' => '',
                'price' => 1100.00,
                'stock_quantity' => 20,
                'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            ],
            [
                'combination_values' => ['Size' => 'L', 'Color' => 'Red'],
                'sku' => '',
                'price' => 1200.00,
                'stock_quantity' => 15,
                'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
            ]
        ];

        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->call('selectLeafForAddProduct', $this->category->id)
            ->set('basicInfo.title', 'Variant Polo')
            ->set('basicInfo.description', 'Variant polo t-shirts')
            ->set('variationGroups', $groupsPayload)
            ->set('combinations', $combinationsPayload)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('title', 'Variant Polo')->first();
        $this->assertNotNull($product);
        $this->assertEquals(35, $product->stock_quantity); // 20 + 15

        $this->assertDatabaseHas('product_combinations', [
            'product_id' => $product->id,
            'sku' => 'KT-P-0001-M-RED',
            'stock_quantity' => 20,
        ]);

        $this->assertDatabaseHas('product_combinations', [
            'product_id' => $product->id,
            'sku' => 'KT-P-0001-L-RED',
            'stock_quantity' => 15,
        ]);
    }

    public function test_can_set_customer_level_pricing_override()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->call('selectLeafForAddProduct', $this->category->id)
            ->set('basicInfo.title', 'Special Linen')
            ->set('basicInfo.description', 'Description')
            ->set('pricingOverrides.' . $this->customerLevel->id, 15.00) // Custom 15% discount
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('title', 'Special Linen')->first();
        $this->assertDatabaseHas('product_customer_level_prices', [
            'product_id' => $product->id,
            'customer_level_id' => $this->customerLevel->id,
            'discount_percentage' => 15.00,
        ]);
    }

    public function test_can_toggle_product_status()
    {
        $product = Product::create([
            'title' => 'Classic Tee',
            'sku' => 'KT-P-0005',
            'base_price' => 500,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->call('toggleStatus', $product->id);

        $this->assertFalse((bool)$product->fresh()->is_active);
    }

    public function test_can_delete_product()
    {
        $product = Product::create([
            'title' => 'Classic Tee To Delete',
            'sku' => 'KT-P-0006',
            'base_price' => 500,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(ProductIndexPage::class)
            ->call('confirmDelete', $product->id)
            ->call('delete');

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_details_page_loads_data()
    {
        $product = Product::create([
            'title' => 'Polo Details Test',
            'sku' => 'KT-P-0007',
            'base_price' => 800,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::test(ProductShowPage::class, ['productId' => $product->id])
            ->assertSee('Polo Details Test')
            ->assertSee('KT-P-0007')
            ->assertSee('₹800.00');
    }

    public function test_admin_without_categories_permission_cannot_configure_defaults()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $accessProducts = Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'web']);
        
        $adminUser = User::factory()->create();
        $adminUser->assignRole($adminRole);
        $adminUser->givePermissionTo($accessProducts);

        Livewire::actingAs($adminUser)
            ->test(ProductIndexPage::class)
            ->call('openSelectLeafForDefaults')
            ->assertStatus(403);
    }

    public function test_admin_with_categories_permission_can_configure_defaults()
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $accessProducts = Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'web']);
        $accessCategories = Permission::firstOrCreate(['name' => 'access categories', 'guard_name' => 'web']);
        
        $adminUser = User::factory()->create();
        $adminUser->assignRole($adminRole);
        $adminUser->givePermissionTo($accessProducts);
        $adminUser->givePermissionTo($accessCategories);

        Livewire::actingAs($adminUser)
            ->test(ProductIndexPage::class)
            ->call('openSelectLeafForDefaults')
            ->assertStatus(200);
    }
}
