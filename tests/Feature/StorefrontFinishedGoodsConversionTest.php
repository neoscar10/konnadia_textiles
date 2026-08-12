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
            null,
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
            null,
            104,
            [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1],
            ]
        );
    }

    /** @test */
    public function it_integrates_conversion_modal_via_job_index_livewire()
    {
        $this->actingAs($this->admin);

        Livewire::test(JobIndexPage::class)
            ->set('target_product_id', $this->storefrontSetProduct->id)
            ->set('assembled_sets_quantity', 100)
            ->set('conversionComponents', [
                ['production_job_id' => $this->jobBedSheet->id, 'quantity_per_set' => 1],
                ['production_job_id' => $this->jobPillowCase->id, 'quantity_per_set' => 2],
            ])
            ->call('processConversion')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertEquals(100, $this->storefrontSetProduct->fresh()->stock_quantity);
        $this->assertEquals(3, $this->jobBedSheet->fresh()->remaining_unconverted_quantity);
    }
}
