<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\FabricWidth;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\Task;
use App\Models\ProductionBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminManufacturingProductsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected string $token;
    protected Task $taskCutting;
    protected Task $taskStitching;
    protected FabricWidth $width44;
    protected FabricWidth $width58;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $permProduction = Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        $superRole->givePermissionTo([$permProduction]);

        $this->superAdmin = User::factory()->create(['email' => 'mfg_admin@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->token = JWTAuth::fromUser($this->superAdmin);

        // Seed default tasks and fabric widths
        $this->taskCutting = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-CUT',
            'status' => true,
        ]);
        $this->taskStitching = Task::create([
            'name' => 'Stitching',
            'code' => 'TSK-STITCH',
            'status' => true,
        ]);

        $this->width44 = FabricWidth::create(['name' => '44 Inch', 'value' => 44.00, 'unit' => 'IN', 'status' => true]);
        $this->width58 = FabricWidth::create(['name' => '58 Inch', 'value' => 58.00, 'unit' => 'IN', 'status' => true]);
    }

    /** @test */
    public function test_manufacturing_product_category_api_crud_default_tasks_and_protection(): void
    {
        // 1. Create Category with default task sequence
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/product-categories', [
                'name' => 'Cushion Covers Category',
                'status' => true,
                'default_tasks' => [
                    [
                        'task_id' => $this->taskCutting->id,
                        'sequence_number' => 1,
                        'standard_labor_rate' => 12.50,
                        'is_final_step' => false,
                    ],
                    [
                        'task_id' => $this->taskStitching->id,
                        'sequence_number' => 2,
                        'standard_labor_rate' => 25.00,
                        'is_final_step' => true,
                    ]
                ],
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cushion Covers Category')
            ->assertJsonCount(2, 'data.default_tasks');

        $catId = $createResp->json('data.id');

        // 2. Options lookup
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/product-categories/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Show Category
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/admin/production/product-categories/{$catId}");

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.default_tasks.0.code', 'TSK-CUT');

        // 4. Update Category
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/admin/production/product-categories/{$catId}", [
                'name' => 'Premium Cushion Covers Category',
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Premium Cushion Covers Category');

        // 5. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/product-categories/{$catId}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', false);

        // Reactivate for deletion test
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/product-categories/{$catId}/toggle-status");

        // 6. Delete Category Protection
        $cat = ManufacturingProductCategory::findOrFail($catId);
        ManufacturingProduct::create([
            'name' => 'Linked Cushion Product',
            'code' => 'MP-LINK-01',
            'manufacturing_product_category_id' => $cat->id,
            'status' => 'active',
        ]);

        $deleteFailResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/product-categories/{$catId}");

        $deleteFailResp->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('linked_products_count', 1);

        $this->assertDatabaseHas('manufacturing_product_categories', ['id' => $catId]);
    }

    /** @test */
    public function test_manufacturing_product_options_endpoint(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Bedding', 'status' => true]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/manufacturing-products/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'auto_generated_code',
                    'categories',
                    'fabric_widths',
                    'available_tasks',
                    'subsidiary_raw_materials',
                    'stitching_raw_materials',
                    'storefront_products',
                    'storefront_combinations',
                ]
            ]);
    }

    /** @test */
    public function test_manufacturing_product_api_creation_with_multi_patterns_and_bom(): void
    {
        Storage::fake('public');

        $cat = ManufacturingProductCategory::create(['name' => 'Table Runners', 'status' => true]);

        // Create subsidiary raw material
        $rawCat = RawMaterialCategory::create(['name' => 'Threads & Accessories', 'code' => 'CAT-ACC', 'unit_type' => 'other', 'status' => true]);
        $subMaterial = RawMaterial::create([
            'name' => 'Golden Tassel',
            'code' => 'RM-TASSEL',
            'raw_material_category_id' => $rawCat->id,
            'unit' => 'Pieces',
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->image('runner.jpg', 600, 600);

        $payload = [
            'name' => 'Embroidered Silk Table Runner',
            'manufacturing_product_category_id' => $cat->id,
            'status' => 'active',
            'standard_labor_rate' => 45.00,
            'image' => $file,
            'is_common_subsidiary' => true,
            'is_subsidiary_used' => true,
            'subsidiary_materials' => [
                [
                    'raw_material_id' => $subMaterial->id,
                    'consumption_quantity' => 4,
                ]
            ],
            'patterns' => [
                [
                    'name' => 'Standard Runner Fold',
                    'fabric_width_id' => $this->width44->id,
                    'fabric_length' => 2.25,
                    'fabric_length_unit' => 'm',
                    'standard_labor_rate' => 45.00,
                    'widths' => [
                        [
                            'fabric_width_id' => $this->width44->id,
                            'fabric_length' => 2.25,
                            'fabric_length_unit' => 'm',
                        ],
                        [
                            'fabric_width_id' => $this->width58->id,
                            'fabric_length' => 1.80,
                            'fabric_length_unit' => 'm',
                        ]
                    ],
                    'tasks' => [
                        [
                            'task_id' => $this->taskCutting->id,
                            'sequence_number' => 1,
                            'standard_labor_rate' => 15.00,
                            'is_final_step' => false,
                        ],
                        [
                            'task_id' => $this->taskStitching->id,
                            'sequence_number' => 2,
                            'standard_labor_rate' => 30.00,
                            'is_final_step' => true,
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/v1/admin/production/manufacturing-products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Embroidered Silk Table Runner')
            ->assertJsonPath('data.patterns.0.name', 'Standard Runner Fold')
            ->assertJsonCount(2, 'data.patterns.0.widths')
            ->assertJsonCount(1, 'data.subsidiary_materials');

        $productId = $response->json('data.id');

        $this->assertDatabaseHas('manufacturing_products', [
            'id' => $productId,
            'name' => 'Embroidered Silk Table Runner',
            'manufacturing_product_category_id' => $cat->id,
        ]);

        $this->assertDatabaseHas('manufacturing_product_patterns', [
            'manufacturing_product_id' => $productId,
            'name' => 'Standard Runner Fold',
        ]);
    }

    /** @test */
    public function test_manufacturing_product_index_detail_update_toggle_and_delete_protection(): void
    {
        $cat = ManufacturingProductCategory::create(['name' => 'Curtains', 'status' => true]);

        $product = ManufacturingProduct::create([
            'name' => 'Blackout Velvet Curtain',
            'code' => 'MP-CURT-01',
            'manufacturing_product_category_id' => $cat->id,
            'status' => 'active',
            'standard_labor_rate' => 50.00,
        ]);

        // 1. Index listing
        $indexResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/admin/production/manufacturing-products?search=Velvet');

        $indexResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');

        // 2. Show Detail
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/admin/production/manufacturing-products/{$product->id}");

        $showResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'MP-CURT-01');

        // 3. Update Product
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/v1/admin/production/manufacturing-products/{$product->id}", [
                'name' => 'Super Blackout Velvet Curtain',
                'manufacturing_product_category_id' => $cat->id,
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Super Blackout Velvet Curtain');

        // 4. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/v1/admin/production/manufacturing-products/{$product->id}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'inactive');

        // 5. Delete Protection when linked to Production Batch
        ProductionBatch::create([
            'batch_number' => 'BATCH-TEST-001',
            'manufacturing_product_id' => $product->id,
            'planned_quantity' => 10,
            'completed_quantity' => 0,
            'status' => 'in_production',
        ]);

        $deleteFailResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/v1/admin/production/manufacturing-products/{$product->id}");

        $deleteFailResp->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('manufacturing_products', ['id' => $product->id]);
    }

    /** @test */
    public function test_factory_direct_alias_endpoints_work(): void
    {
        $aliasCatResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/product-categories');
        $aliasCatResp->assertStatus(200)->assertJsonPath('success', true);

        $aliasProdResp = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/factory/products');
        $aliasProdResp->assertStatus(200)->assertJsonPath('success', true);
    }
}
