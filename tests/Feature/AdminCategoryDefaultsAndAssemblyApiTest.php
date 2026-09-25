<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\ManufacturingProduct;
use App\Models\RawMaterial;
use App\Models\CustomerLevel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryDefaultsAndAssemblyApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $leafCategory;
    protected ManufacturingProduct $mfgProduct1;
    protected ManufacturingProduct $mfgProduct2;
    protected RawMaterial $packagingMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');

        $this->leafCategory = Category::create([
            'name' => 'Premium Bedsheet Sets',
            'slug' => 'premium-bedsheet-sets',
            'is_leaf' => true,
            'is_active' => true,
        ]);

        CustomerLevel::create([
            'name' => 'VIP Wholesale',
            'discount_percentage' => 15.0,
            'is_active' => true,
        ]);

        $this->mfgProduct1 = ManufacturingProduct::create([
            'name' => 'Bedsheet King',
            'code' => 'MP-BED-K',
            'status' => 'active',
        ]);

        $this->mfgProduct2 = ManufacturingProduct::create([
            'name' => 'Pillowcase Standard',
            'code' => 'MP-PIL-S',
            'status' => 'active',
        ]);

        $rmCat = \App\Models\RawMaterialCategory::create([
            'name' => 'Packaging Materials',
            'code' => 'CAT-PKG',
        ]);

        $this->packagingMaterial = RawMaterial::create([
            'name' => 'Polybag Clear XL',
            'code' => 'RM-PKG-01',
            'unit' => 'Pcs',
            'raw_material_category_id' => $rmCat->id,
            'status' => 'active',
        ]);
    }

    public function test_get_category_defaults_returns_defaults_assembly_and_picker_options()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/categories/{$this->leafCategory->id}/defaults");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'category_id' => $this->leafCategory->id,
                    'category_name' => 'Premium Bedsheet Sets',
                    'is_leaf' => true,
                ]
            ])
            ->assertJsonStructure([
                'data' => [
                    'defaults' => [
                        'pricingOverrides',
                        'units',
                    ],
                    'assembly_config',
                    'picker_options' => [
                        'manufacturing_products',
                        'packaging_materials',
                    ]
                ]
            ]);
    }

    public function test_save_category_defaults_saves_pricing_units_and_assembly_mapping()
    {
        $payload = [
            'base_price' => 1250.00,
            'description' => 'Default config for premium bedsheets',
            'hsn_code' => '6302',
            'gst_percentage' => 12,
            'minimum_order_quantity' => 2,
            'product_type' => 'manufactured',
            'units' => [
                'level1_name' => 'Set',
                'level1_code' => 'set',
                'level2_name' => 'Carton',
                'level2_code' => 'ctn',
                'level2_conversion' => 10,
            ],
            'pricingOverrides' => [],
            'components' => [
                ['manufacturing_product_id' => $this->mfgProduct1->id, 'quantity' => 1],
                ['manufacturing_product_id' => $this->mfgProduct2->id, 'quantity' => 2],
            ],
            'packaging_items' => [
                ['raw_material_id' => $this->packagingMaterial->id, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("/api/v1/admin/categories/{$this->leafCategory->id}/defaults", $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->leafCategory->refresh();
        $this->assertEquals(1250.00, $this->leafCategory->default_product_config['base_price']);
        $this->assertEquals('manufactured', $this->leafCategory->default_product_config['product_type']);

        $this->assertDatabaseHas('front_end_products', [
            'category_id' => $this->leafCategory->id,
            'name' => 'Premium Bedsheet Sets',
        ]);

        $feProduct = \App\Models\FrontEndProduct::where('category_id', $this->leafCategory->id)->first();
        $this->assertCount(2, $feProduct->components);
        $this->assertCount(1, $feProduct->packagingItems);
    }
}
