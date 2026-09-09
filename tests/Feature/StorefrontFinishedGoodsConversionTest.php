<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Product;
use App\Models\Category;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use App\Livewire\Admin\Production\JobIndexPage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontFinishedGoodsConversionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProduct $mProductBedSheet;
    protected ManufacturingProduct $mProductPillowCase;
    protected Product $storefrontSetProduct;
    protected ProductionJob $jobBedSheet;
    protected ProductionJob $jobPillowCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $mpCat = ManufacturingProductCategory::create(['name' => 'Bedding']);

        $this->mProductBedSheet = ManufacturingProduct::create([
            'name' => 'King Size Bed Sheet',
            'manufacturing_product_category_id' => $mpCat->id,
            'status' => 'active',
        ]);

        $this->mProductPillowCase = ManufacturingProduct::create([
            'name' => 'Standard Pillow Case',
            'manufacturing_product_category_id' => $mpCat->id,
            'status' => 'active',
        ]);

        $spCat = Category::create(['name' => 'Storefront Sets', 'slug' => 'storefront-sets']);

        $this->storefrontSetProduct = Product::create([
            'title' => 'Royal Cotton Bedding Set',
            'sku' => 'SET-ROYAL-001',
            'base_price' => 2500.00,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $this->storefrontSetProduct->categories()->attach($spCat->id);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-9999',
            'supervisor_id' => $this->admin->id,
            'planned_quantity' => 300,
            'status' => 'Completed',
        ]);

        // Job 1: 103 Bed Sheets completed
        $this->jobBedSheet = ProductionJob::create([
            'job_code' => 'JOB-2026-0010',
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $this->mProductBedSheet->id,
            'target_quantity' => 103,
            'converted_quantity' => 0,
            'status' => 'completed',
        ]);

        // Job 2: 200 Pillow Cases completed
        $this->jobPillowCase = ProductionJob::create([
            'job_code' => 'JOB-2026-0011',
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $this->mProductPillowCase->id,
            'target_quantity' => 200,
            'converted_quantity' => 0,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_converts_multi_job_components_into_storefront_bundle_sets()
    {
        $service = new FinishedGoodsConversionService();

        // 1 Bed Sheet + 2 Pillow Cases = 1 Storefront Set
        // Convert 100 sets -> needs 100 Bed Sheets & 200 Pillow Cases
        $bundle = $service->convertJobsToStorefrontBundle(
            $this->storefrontSetProduct->id,
            100, // 100 sets
            [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1],
                ['production_job_id' => $this->jobPillowCase->id, 'quantity_per_set' => 2],
            ],
            'Converted 100 sets'
        );

        $this->assertNotNull($bundle);
        $this->assertEquals(100, $this->storefrontSetProduct->fresh()->stock_quantity);

        // Check Job Bed Sheet: 100 converted out of 103 -> 3 remaining
        $this->assertEquals(100, $this->jobBedSheet->fresh()->converted_quantity);
        $this->assertEquals(3, $this->jobBedSheet->fresh()->remaining_unconverted_quantity);
        $this->assertEquals('partially_converted', $this->jobBedSheet->fresh()->conversion_status);

        // Check Job Pillow Case: 200 converted out of 200 -> 0 remaining
        $this->assertEquals(200, $this->jobPillowCase->fresh()->converted_quantity);
        $this->assertEquals(0, $this->jobPillowCase->fresh()->remaining_unconverted_quantity);
        $this->assertEquals('fully_converted', $this->jobPillowCase->fresh()->conversion_status);
    }

    /** @test */
    public function it_prevents_conversion_exceeding_available_unconverted_stock()
    {
        $this->expectException(\Exception::class);

        $service = new FinishedGoodsConversionService();

        // Attempting to convert 104 sets when only 103 Bed Sheets exist
        $service->convertJobsToStorefrontBundle(
            $this->storefrontSetProduct->id,
            104,
            [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1],
            ]
        );
    }

    /** @test */
    public function it_integrates_conversion_modal_via_job_index_livewire_with_automatic_set_calculation()
    {
        $this->actingAs($this->admin);

        // Processing 50 Bedsheets (1/set) and 105 Pillow Cases (2/set) -> 50 sets, 5 pillow cases leftover
        Livewire::test(JobIndexPage::class)
            ->set('target_product_id', $this->storefrontSetProduct->id)
            ->set('conversionComponents', [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1, 'total_pieces_input' => 50],
                ['production_job_id' => $this->jobPillowCase->id, 'quantity_per_set' => 2, 'total_pieces_input' => 105],
            ])
            ->call('processConversion')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        // +50 stock created for storefront product
        $this->assertEquals(50, $this->storefrontSetProduct->fresh()->stock_quantity);
        // Job Bed Sheet: 50 consumed out of 103 -> 53 remaining
        $this->assertEquals(53, $this->jobBedSheet->fresh()->remaining_unconverted_quantity);
        // Job Pillow Case: 100 consumed out of 200 (105 entered, 100 consumed) -> 100 remaining
        $this->assertEquals(100, $this->jobPillowCase->fresh()->remaining_unconverted_quantity);
    }

    /** @test */
    public function it_converts_with_packaging_materials_successfully()
    {
        $rawCat = \App\Models\RawMaterialCategory::create(['name' => 'Packaging', 'code' => 'PKG']);
        $rawMat = \App\Models\RawMaterial::create([
            'raw_material_category_id' => $rawCat->id,
            'name' => 'Polybag 12x18',
            'code' => 'RM-PKG-001',
            'unit' => 'Pieces',
        ]);

        $invBatch = \App\Models\InventoryBatch::create([
            'raw_material_id' => $rawMat->id,
            'batch_number' => 'BATCH-PKG-001',
            'received_quantity' => 100,
            'balance_quantity' => 100,
            'unit' => 'Pieces',
            'unit_cost' => 10.00,
        ]);

        $service = new FinishedGoodsConversionService();

        $bundle = $service->convertJobsToStorefrontBundle(
            $this->storefrontSetProduct->id,
            10,
            [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1],
            ],
            'Notes',
            [
                ['raw_material_id' => $rawMat->id, 'quantity_used' => 20],
            ]
        );

        $this->assertNotNull($bundle);
        $this->assertEquals(80, $invBatch->fresh()->balance_quantity);
        $this->assertDatabaseHas('job_material_consumptions', [
            'inventory_batch_id' => $invBatch->id,
            'quantity_consumed' => 20,
        ]);
    }

    /** @test */
    public function it_supports_creating_new_or_topping_up_existing_storefront_product_during_conversion()
    {
        $feCategory = Category::create(['name' => 'Bedsheets', 'slug' => 'bedsheets']);

        $feProduct = \App\Models\FrontEndProduct::create([
            'name' => 'Luxury Satin Bedsheet',
            'sku' => 'FE-SATIN-001',
            'category_id' => $feCategory->id,
            'is_active' => true,
        ]);

        \App\Models\FrontEndProductComponent::create([
            'front_end_product_id' => $feProduct->id,
            'manufacturing_product_id' => $this->mProductBedSheet->id,
            'quantity' => 1,
        ]);

        $service = new FinishedGoodsConversionService();

        // 1. Convert with mode = 'existing' targeting $this->storefrontSetProduct
        $batchExisting = $service->convertFrontEndProductBatch([
            'front_end_product_id' => $feProduct->id,
            'converted_qty' => 5,
            'unit' => 'Piece (Pcs)',
            'unit_factor' => 1,
            'design_id' => 'DSG-TEST-01',
            'storefront_mode' => 'existing',
            'existing_storefront_product_id' => $this->storefrontSetProduct->id,
        ]);

        $this->assertNotNull($batchExisting);
        $this->assertEquals(5, $this->storefrontSetProduct->fresh()->stock_quantity);

        // 2. Convert with mode = 'new' (creates/updates matching product in category)
        $batchNew = $service->convertFrontEndProductBatch([
            'front_end_product_id' => $feProduct->id,
            'converted_qty' => 12,
            'unit' => 'Piece (Pcs)',
            'unit_factor' => 1,
            'design_id' => 'DSG-TEST-02',
            'storefront_mode' => 'new',
        ]);

        $this->assertNotNull($batchNew);
        $newProduct = Product::where('sku', 'FE-SATIN-001')->first();
        $this->assertNotNull($newProduct);
        $this->assertEquals('Luxury Satin Bedsheet', $newProduct->title);
        $this->assertEquals(12, $newProduct->stock_quantity);
    }
}

