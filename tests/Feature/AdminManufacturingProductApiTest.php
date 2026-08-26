<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManufacturingProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $category;
    protected Task $cuttingTask;
    protected Task $stitchingTask;
    protected Task $packingTask;
    protected RawMaterial $subMaterial;
    protected RawMaterial $stitchMaterial;
    protected RawMaterial $pkgMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access production');

        $this->category = ManufacturingProductCategory::create([
            'name' => 'Bedding & Linen',
            'code' => 'CAT-BED',
            'status' => true,
        ]);

        $this->cuttingTask = Task::create(['name' => 'Cutting', 'code' => 'CUT-01', 'status' => true]);
        $this->stitchingTask = Task::create(['name' => 'Stitching', 'code' => 'STITCH-01', 'status' => true]);
        $this->packingTask = Task::create(['name' => 'Packing', 'code' => 'PKG-01', 'status' => true]);

        $subCat = RawMaterialCategory::create(['name' => 'Subsidiary', 'code' => 'CAT-SUB']);
        $stitchCat = RawMaterialCategory::create(['name' => 'Stitching', 'code' => 'CAT-STITCH']);
        $pkgCat = RawMaterialCategory::create(['name' => 'Packaging', 'code' => 'CAT-PKG']);

        $this->subMaterial = RawMaterial::create([
            'name' => 'Zipper 24 Inch',
            'code' => 'RM-SUB-001',
            'raw_material_category_id' => $subCat->id,
            'unit' => 'Pcs',
            'status' => true,
        ]);

        $this->stitchMaterial = RawMaterial::create([
            'name' => 'Cotton Thread Spool',
            'code' => 'RM-STITCH-001',
            'raw_material_category_id' => $stitchCat->id,
            'unit' => 'Spool',
            'status' => true,
        ]);

        $this->pkgMaterial = RawMaterial::create([
            'name' => 'Poly Bag Heavy Duty',
            'code' => 'RM-PKG-001',
            'raw_material_category_id' => $pkgCat->id,
            'unit' => 'Pcs',
            'status' => true,
        ]);
    }

    /** @test */
    public function it_fetches_lookup_options_for_product_creation()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/manufacturing-products/options');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'auto_generated_code',
                    'categories',
                    'available_tasks',
                    'subsidiary_raw_materials',
                    'stitching_raw_materials',
                    'packaging_raw_materials',
                ],
            ]);
    }

    /** @test */
    public function it_creates_a_new_manufacturing_product_with_routing_and_boms()
    {
        $payload = [
            'name' => 'Luxury Satin Bedsheet',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'standard_labor_rate' => 25.00,
            'is_fabric_used' => true,
            'standard_fabric_width' => 90.00,
            'standard_fabric_length' => 2.75,
            'fabric_width_unit' => 'inch',
            'fabric_length_unit' => 'meter',
            'is_subsidiary_used' => true,
            'subsidiary_materials' => [
                ['raw_material_id' => $this->subMaterial->id, 'consumption_quantity' => 1.0],
            ],
            'is_stitching_used' => true,
            'stitching_materials' => [$this->stitchMaterial->id],
            'is_packaging_used' => false,
            'tasks' => [
                ['task_id' => $this->cuttingTask->id, 'sequence_number' => 1, 'standard_labor_rate' => 10.00, 'is_final_step' => false],
                ['task_id' => $this->stitchingTask->id, 'sequence_number' => 2, 'standard_labor_rate' => 15.00, 'is_final_step' => false],
                ['task_id' => $this->packingTask->id, 'sequence_number' => 3, 'standard_labor_rate' => 5.00, 'is_final_step' => true],
            ],
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/production/manufacturing-products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Luxury Satin Bedsheet',
                    'manufacturing_product_category_id' => $this->category->id,
                    'is_fabric_used' => true,
                ],
            ]);

        $this->assertDatabaseHas('manufacturing_products', [
            'name' => 'Luxury Satin Bedsheet',
            'manufacturing_product_category_id' => $this->category->id,
        ]);

        $product = ManufacturingProduct::where('name', 'Luxury Satin Bedsheet')->first();
        $this->assertCount(3, $product->tasks);
        $this->assertCount(1, $product->subsidiaryMaterials);
        $this->assertCount(1, $product->stitchingMaterials);
        $this->assertCount(0, $product->packagingMaterials);
    }

    /** @test */
    public function it_lists_and_filters_manufacturing_products()
    {
        ManufacturingProduct::create([
            'name' => 'Target Bedsheet',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
        ]);

        ManufacturingProduct::create([
            'name' => 'Other Item',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/manufacturing-products?search=Target');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Target Bedsheet');
    }

    /** @test */
    public function it_toggles_product_status()
    {
        $product = ManufacturingProduct::create([
            'name' => 'Toggle Product',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/production/manufacturing-products/{$product->id}/toggle-status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'inactive');

        $this->assertEquals('inactive', $product->fresh()->status);
    }
}
