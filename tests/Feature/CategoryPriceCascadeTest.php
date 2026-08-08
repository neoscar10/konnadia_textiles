<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\CustomerLevel;
use App\Models\ProductCustomerLevelPrice;
use App\Services\Catalog\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\Categories\CategoryIndexPage;

class CategoryPriceCascadeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin = User::factory()->create([
            'email' => 'cat_price_admin@konnadia.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_updating_leaf_category_default_price_cascades_to_all_products_under_it(): void
    {
        $parent = Category::create([
            'name' => 'Fabrics',
            'slug' => 'fabrics',
            'is_leaf' => false,
            'is_active' => true,
        ]);

        $leafCategory = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Linen Ready-Made',
            'slug' => 'linen-ready-made',
            'is_leaf' => true,
            'is_active' => true,
        ]);

        // Create 2 products under the leaf category with initial base_price 100.00
        $product1 = Product::create([
            'title' => 'Linen Sheet Alpha',
            'sku' => 'LINEN-001',
            'base_price' => 100.00,
            'description' => 'Test description',
            'is_active' => true,
            'hsn_code' => '5208',
            'gst_percentage' => 5.0,
            'minimum_order_quantity' => 1,
            'product_type' => 'retail',
        ]);
        $product1->categories()->attach($leafCategory->id);

        $product2 = Product::create([
            'title' => 'Linen Sheet Beta',
            'sku' => 'LINEN-002',
            'base_price' => 100.00,
            'description' => 'Test description 2',
            'is_active' => true,
            'hsn_code' => '5208',
            'gst_percentage' => 5.0,
            'minimum_order_quantity' => 1,
            'product_type' => 'retail',
        ]);
        $product2->categories()->attach($leafCategory->id);

        $customerLevel = CustomerLevel::create([
            'name' => 'Gold Dealer',
            'slug' => 'gold-dealer',
            'is_active' => true,
        ]);

        // Invoke saveCategoryDefaults with new base_price = 250.00 and pricing override
        $categoryService = app(CategoryService::class);
        $defaults = [
            'base_price' => '250.00',
            'description' => 'Default linen description',
            'hsn_code' => '5208',
            'gst_percentage' => '5.0',
            'minimum_order_quantity' => 10,
            'product_type' => 'retail',
            'pricingOverrides' => [
                $customerLevel->id => 15.0,
            ],
            'units' => [
                'level1_name' => 'Piece',
                'level1_code' => 'pcs',
            ]
        ];

        $categoryService->saveCategoryDefaults($leafCategory, $defaults);

        // Verify leaf category default config was updated
        $leafCategory->refresh();
        $this->assertEquals(250.00, $leafCategory->default_product_config['base_price']);

        // Verify attached product base_prices were cascaded to 250.00
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(250.00, (float)$product1->base_price);
        $this->assertEquals(250.00, (float)$product2->base_price);

        // Verify customer level pricing overrides were attached/updated
        $this->assertDatabaseHas('product_customer_level_prices', [
            'product_id' => $product1->id,
            'customer_level_id' => $customerLevel->id,
            'discount_percentage' => 15.0,
        ]);
        $this->assertDatabaseHas('product_customer_level_prices', [
            'product_id' => $product2->id,
            'customer_level_id' => $customerLevel->id,
            'discount_percentage' => 15.0,
        ]);
    }

    public function test_category_defaults_livewire_component_triggers_price_cascade(): void
    {
        $leafCategory = Category::create([
            'name' => 'Silk Fabrics',
            'slug' => 'silk-fabrics',
            'is_leaf' => true,
            'is_active' => true,
        ]);

        $product = Product::create([
            'title' => 'Silk Saree',
            'sku' => 'SILK-001',
            'base_price' => 500.00,
            'description' => 'Test silk saree',
            'is_active' => true,
            'hsn_code' => '5007',
            'gst_percentage' => 12.0,
            'minimum_order_quantity' => 1,
            'product_type' => 'retail',
        ]);
        $product->categories()->attach($leafCategory->id);

        $this->actingAs($this->admin);

        Livewire::test(CategoryIndexPage::class)
            ->set('currentCategoryId', $leafCategory->id)
            ->set('categoryDefaults', [
                'base_price' => '750.00',
                'description' => 'Silk category default description',
                'hsn_code' => '5007',
                'gst_percentage' => '12.0',
                'minimum_order_quantity' => 5,
                'product_type' => 'retail',
                'pricingOverrides' => [],
                'units' => [
                    'level1_name' => 'Piece',
                    'level1_code' => 'pcs',
                ],
            ])
            ->call('saveCategoryDefaults')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $product->refresh();
        $this->assertEquals(750.00, (float)$product->base_price);
    }
}
