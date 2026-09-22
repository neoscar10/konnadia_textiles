<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FrontEndProduct;
use App\Models\FrontEndProductComponent;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\SpareProduct;
use App\Models\User;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpareProductAndBatchConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'email' => 'admin_spare@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($admin);
    }

    public function test_batch_is_fully_completed_when_jobs_finished_and_discrepancies_resolved()
    {
        $mfg = ManufacturingProduct::create([
            'name' => 'Bedsheet King',
            'code' => 'MP-BS-01',
            'is_active' => true,
        ]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-100',
            'manufacturing_product_id' => $mfg->id,
            'planned_quantity' => 100,
            'status' => 'In Progress',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-100',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg->id,
            'target_quantity' => 100,
            'initial_cut_quantity' => 100,
            'status' => 'in_progress',
        ]);

        $this->assertFalse($batch->isFullyCompleted());

        $job->update([
            'status' => 'completed',
            'completed_quantity' => 100,
            'final_produced_yield' => 100,
        ]);

        $this->assertTrue($batch->isFullyCompleted());
    }

    public function test_get_design_ids_with_product_counts()
    {
        $mfg = ManufacturingProduct::create([
            'name' => 'Pillow Case',
            'code' => 'MP-PC-01',
            'is_active' => true,
        ]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-200',
            'manufacturing_product_id' => $mfg->id,
            'planned_quantity' => 40,
            'status' => 'Completed',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-200',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg->id,
            'target_quantity' => 40,
            'completed_quantity' => 40,
            'status' => 'completed',
        ]);

        $designs = $batch->getDesignIdsWithProductCounts();

        $this->assertNotEmpty($designs);
        $this->assertArrayHasKey('design_id', $designs[0]);
        $this->assertGreaterThan(0, $designs[0]['total_produced_qty']);
    }

    public function test_get_design_ids_groups_same_pattern_case_insensitively_without_duplicates()
    {
        $mfg1 = ManufacturingProduct::create([
            'name' => 'Yawa bedsheet',
            'code' => 'MP-YB-01',
            'is_active' => true,
        ]);

        $mfg2 = ManufacturingProduct::create([
            'name' => 'Burnaboy Gram',
            'code' => 'MP-BG-01',
            'is_active' => true,
        ]);

        $pattern1 = \App\Models\Pattern::create(['name' => 'Pattern 1']);
        $pattern2 = \App\Models\Pattern::create(['name' => 'pattern 1']);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-0033',
            'planned_quantity' => 100,
            'status' => 'Completed',
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-001',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg1->id,
            'pattern_id' => $pattern1->id,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'status' => 'completed',
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-002',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg2->id,
            'pattern_id' => $pattern2->id,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'status' => 'completed',
        ]);

        $designs = $batch->getDesignIdsWithProductCounts();

        // Must return only 1 single design option for 'Pattern 1' / 'pattern 1'
        $this->assertCount(1, $designs);
        $this->assertEquals(100, $designs[0]['total_produced_qty']);

        // Must list both products under products key
        $products = $designs[0]['products'];
        $this->assertCount(2, $products);
        $productNames = collect($products)->pluck('name')->toArray();
        $this->assertContains('Yawa bedsheet', $productNames);
        $this->assertContains('Burnaboy Gram', $productNames);
    }

    public function test_conversion_creates_spare_products_for_leftovers()
    {
        $category = Category::create([
            'name' => 'Royal Touch Set',
            'slug' => 'royal-touch-set',
            'is_active' => true,
        ]);

        $feProduct = FrontEndProduct::create([
            'category_id' => $category->id,
            'name' => 'Royal Touch Bedsheet Set',
            'sku' => 'FE-RT-SET',
            'is_active' => true,
        ]);

        $mfg1 = ManufacturingProduct::create(['name' => 'Bedsheet Item', 'code' => 'MP-BS-ITEM', 'is_active' => true]);
        $mfg2 = ManufacturingProduct::create(['name' => 'Pillow Item', 'code' => 'MP-PC-ITEM', 'is_active' => true]);

        FrontEndProductComponent::create(['front_end_product_id' => $feProduct->id, 'manufacturing_product_id' => $mfg1->id, 'quantity' => 1]);
        FrontEndProductComponent::create(['front_end_product_id' => $feProduct->id, 'manufacturing_product_id' => $mfg2->id, 'quantity' => 2]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-TEST-300',
            'planned_quantity' => 50,
            'status' => 'Completed',
        ]);

        // Job 1: 21 Bedsheets produced
        $job1 = ProductionJob::create([
            'job_code' => 'JOB-BS-300',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg1->id,
            'target_quantity' => 21,
            'completed_quantity' => 21,
            'status' => 'completed',
        ]);

        // Job 2: 40 Pillow cases produced
        $job2 = ProductionJob::create([
            'job_code' => 'JOB-PC-300',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg2->id,
            'target_quantity' => 40,
            'completed_quantity' => 40,
            'status' => 'completed',
        ]);

        $service = resolve(FinishedGoodsConversionService::class);

        // Convert max 20 sets (20 Bedsheets + 40 Pillow cases). 1 Bedsheet leftover!
        $fgBatch = $service->convertCategoryToFinishedGoods([
            'category_id' => $category->id,
            'target_qty' => 20,
            'design_type' => 'new',
            'design_id' => 'DSG-TEST-GOLD',
            'production_batch_id' => $batch->id,
        ]);

        $this->assertEquals(20, $fgBatch->converted_qty);

        // Verify SpareProduct created for 1 leftover bedsheet
        $spare = SpareProduct::where('manufacturing_product_id', $mfg1->id)->where('design_id', 'DSG-TEST-GOLD')->first();
        $this->assertNotNull($spare);
        $this->assertEquals(1, $spare->quantity);
        $this->assertEquals(0, $spare->used_quantity);
        $this->assertEquals(1, $spare->available_quantity);
    }

    public function test_pulling_spare_stock_into_subsequent_conversion()
    {
        $category = Category::create([
            'name' => 'Luxury Set',
            'slug' => 'luxury-set',
            'is_active' => true,
        ]);

        $feProduct = FrontEndProduct::create([
            'category_id' => $category->id,
            'name' => 'Luxury Bedsheet Set',
            'sku' => 'FE-LUX-SET',
            'is_active' => true,
        ]);

        $mfg1 = ManufacturingProduct::create(['name' => 'Luxury Bedsheet', 'code' => 'MP-LUX-BS', 'is_active' => true]);
        $mfg2 = ManufacturingProduct::create(['name' => 'Luxury Pillow', 'code' => 'MP-LUX-PC', 'is_active' => true]);

        FrontEndProductComponent::create(['front_end_product_id' => $feProduct->id, 'manufacturing_product_id' => $mfg1->id, 'quantity' => 1]);
        FrontEndProductComponent::create(['front_end_product_id' => $feProduct->id, 'manufacturing_product_id' => $mfg2->id, 'quantity' => 2]);

        // Create an existing spare product of 1 Bedsheet for DSG-LUX-01
        $spare = SpareProduct::create([
            'manufacturing_product_id' => $mfg1->id,
            'design_id' => 'DSG-LUX-01',
            'quantity' => 1,
            'used_quantity' => 0,
            'notes' => 'Existing spare bedsheet',
        ]);

        // New Batch has 19 Bedsheets and 40 Pillow Cases
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-LUX-400',
            'planned_quantity' => 60,
            'status' => 'Completed',
        ]);

        $job1 = ProductionJob::create([
            'job_code' => 'JOB-LUX-BS',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg1->id,
            'target_quantity' => 19,
            'completed_quantity' => 19,
            'status' => 'completed',
        ]);

        $job2 = ProductionJob::create([
            'job_code' => 'JOB-LUX-PC',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfg2->id,
            'target_quantity' => 40,
            'completed_quantity' => 40,
            'status' => 'completed',
        ]);

        $service = resolve(FinishedGoodsConversionService::class);

        // Convert using 19 bedsheets from batch + 1 spare bedsheet = 20 bedsheets (and 40 pillow cases from batch) -> 20 Sets!
        $fgBatch = $service->convertCategoryToFinishedGoods([
            'category_id' => $category->id,
            'target_qty' => 20,
            'design_type' => 'new',
            'design_id' => 'DSG-LUX-01',
            'production_batch_id' => $batch->id,
            'spare_stock_selections' => [
                ['spare_product_id' => $spare->id, 'quantity_used' => 1],
            ],
        ]);

        $this->assertEquals(20, $fgBatch->converted_qty);

        // Verify spare product updated
        $spare->refresh();
        $this->assertEquals(1, $spare->used_quantity);
        $this->assertEquals(0, $spare->available_quantity);
    }
}
