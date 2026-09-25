<?php

namespace Tests\Feature;

use App\Livewire\Admin\Production\BatchJobsDetailPage;
use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FrontEndProduct;
use App\Models\FrontEndProductComponent;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BatchBarcodePrintingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ProductionBatch $batch;
    protected ProductionJob $job;
    protected Category $category;
    protected FrontEndProduct $feProduct;
    protected ManufacturingProduct $mfgProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->actingAs($this->admin);

        $this->category = Category::create([
            'name' => 'Royal Touch Set',
            'slug' => 'royal-touch-set',
            'is_active' => true,
        ]);

        $this->mfgProduct = ManufacturingProduct::create([
            'name' => 'Royal Bedsheet',
            'code' => 'MP-RB-01',
            'is_active' => true,
        ]);

        $this->feProduct = FrontEndProduct::create([
            'category_id' => $this->category->id,
            'name' => 'Royal Touch Set Product',
            'sku' => 'FE-ROYAL-SET',
            'is_active' => true,
        ]);

        FrontEndProductComponent::create([
            'front_end_product_id' => $this->feProduct->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity' => 1,
        ]);

        $this->batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-0053',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'planned_quantity' => 50,
            'status' => 'Completed',
        ]);

        $this->job = ProductionJob::create([
            'job_code' => 'JOB-2026-0053',
            'production_batch_db_id' => $this->batch->id,
            'production_batch_id' => $this->batch->batch_code,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'final_produced_yield' => 50,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_opens_barcode_print_modal_when_calling_open_print_barcode_modal()
    {
        $fgBatch = FinishedGoodsBatch::create([
            'barcode' => 'FG-DSG0053-2026-0010',
            'front_end_product_id' => $this->feProduct->id,
            'design_id' => 'DSG-0053',
            'converted_qty' => 10,
            'unit' => 'Piece (Pcs)',
            'unit_factor' => 1,
            'converted_date' => now(),
            'is_published' => true,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(BatchJobsDetailPage::class, ['batchCode' => 'PB-2026-0053'])
            ->call('openPrintBarcodeModal', $fgBatch->id)
            ->assertSet('showPrintModal', true)
            ->assertSet('activePrintBatchId', $fgBatch->id)
            ->assertSet('printStickerQty', 10)
            ->assertSee('Print Barcode Stickers')
            ->assertSee($fgBatch->barcode);
    }

    /** @test */
    public function it_automatically_opens_barcode_modal_after_batch_conversion()
    {
        Livewire::test(BatchJobsDetailPage::class, ['batchCode' => 'PB-2026-0053'])
            ->set('selectedBatchDbId', $this->batch->id)
            ->set('selectedBatchCode', $this->batch->batch_code)
            ->set('selectedDesignId', 'DSG-2026-0053')
            ->set('selectedCategoryIdForBatchConv', $this->category->id)
            ->set('prefilledTargetSets', 20)
            ->call('processBatchConversionSubmit')
            ->assertHasNoErrors()
            ->assertSet('showPrintModal', true)
            ->assertSee('Print Barcode Stickers')
            ->assertSee('DSG-2026-0053');

        $fgBatch = FinishedGoodsBatch::where('design_id', 'DSG-2026-0053')->first();
        $this->assertNotNull($fgBatch);
        $this->assertEquals(20, $fgBatch->converted_qty);
    }

    /** @test */
    public function it_renders_converted_finished_goods_lots_table_and_print_barcode_button_on_batch_page()
    {
        $fgBatch = FinishedGoodsBatch::create([
            'barcode' => 'FG-DSG999-2026-0050',
            'front_end_product_id' => $this->feProduct->id,
            'design_id' => 'DSG-999',
            'converted_qty' => 50,
            'unit' => 'Piece (Pcs)',
            'unit_factor' => 1,
            'converted_date' => now(),
            'is_published' => true,
            'created_by' => $this->admin->id,
            'notes' => 'Converted from Production Batch Code PB-2026-0053',
        ]);

        \App\Models\FinishedGoodsBatchItem::create([
            'finished_goods_batch_id' => $fgBatch->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'production_batch_id' => $this->batch->id,
            'production_job_id' => $this->job->id,
            'quantity_used' => 50,
        ]);

        Livewire::test(BatchJobsDetailPage::class, ['batchCode' => 'PB-2026-0053'])
            ->assertSee('Converted Finished Goods Lots')
            ->assertSee($fgBatch->barcode)
            ->assertSee('Print Barcode Labels')
            ->assertSee('Print Barcode');
    }
}
