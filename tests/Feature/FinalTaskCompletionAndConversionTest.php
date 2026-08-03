<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\Product;
use App\Services\Manufacturing\ProductionWorkflowService;
use App\Events\ProductionBatchCompleted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class FinalTaskCompletionAndConversionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $category;
    protected ManufacturingProduct $mProduct;
    protected Task $finalTask;
    protected Product $storefrontProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->category = ManufacturingProductCategory::create([
            'name' => 'Bedsheets',
            'code' => 'CAT-001',
            'status' => true
        ]);

        $this->finalTask = Task::create([
            'name' => 'Packaging',
            'code' => 'TSK-003',
            'status' => true
        ]);

        $this->mProduct = ManufacturingProduct::create([
            'name' => 'Premium Sheet',
            'code' => 'MP-2026-9001',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'standard_labor_rate' => 15.00,
        ]);

        $this->mProduct->tasks()->sync([
            $this->finalTask->id => ['sequence_number' => 1, 'standard_labor_rate' => 10.00, 'is_final_step' => true],
        ]);

        // Create storefront product to map finished goods to
        $this->storefrontProduct = Product::create([
            'title' => 'Premium Sheet Storefront',
            'sku' => 'PREM-SHEET-001',
            'base_price' => 500.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function completing_final_task_sets_completed_at_and_fires_event()
    {
        Event::fake([ProductionBatchCompleted::class]);

        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job = $batch->jobs->first();

        // Complete final step job
        $compResponse = $workflowService->completeJob($job->id);
        $compData = $compResponse->getData(true)['data'];

        $this->assertTrue((bool)$compData['isFinalStep']);

        $batch->refresh();
        $this->assertEquals('Completed', $batch->status);
        $this->assertNotNull($batch->completed_at);
        $this->assertFalse($batch->is_converted);

        Event::assertDispatched(ProductionBatchCompleted::class, function ($event) use ($batch) {
            return $event->batchId === $batch->id;
        });
    }

    /** @test */
    public function batch_is_ready_for_conversion_evaluation()
    {
        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job = $batch->jobs->first();

        // Initially not ready because status is not 'Completed'
        $this->assertFalse($batch->isReadyForConversion());

        // Attempting to set completed directly is blocked by model guard
        $this->expectException(\Exception::class);
        $batch->update(['status' => 'Completed']);
    }

    /** @test */
    public function manual_conversion_attempts_via_component_require_completed_batch()
    {
        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);

        $this->actingAs($this->admin);

        // Redirects because batch is not completed/ready
        Livewire::test(\App\Livewire\Admin\Production\FinishedGoodsConversion::class, ['id' => $batch->id])
            ->assertRedirect(route('admin.production.batches.ledger', $batch->id));
    }

    /** @test */
    public function successful_finished_goods_conversion_updates_stock()
    {
        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job = $batch->jobs->first();

        // Complete final task job
        $workflowService->completeJob($job->id);
        $batch->refresh();

        $this->assertTrue($batch->isReadyForConversion());

        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\Production\FinishedGoodsConversion::class, ['id' => $batch->id])
            ->set('productId', $this->storefrontProduct->id)
            ->set('targetWarehouse', 'Finished Goods WH - Zone A')
            ->set('lotNumber', 'LOT-PREM-SHEET-TEST')
            ->call('convert')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.production.batches.ledger', $batch->id));

        $batch->refresh();
        $this->assertTrue($batch->is_converted);

        $this->storefrontProduct->refresh();
        // Initial 10 + 50 completed batch units = 60
        $this->assertEquals(60, $this->storefrontProduct->stock_quantity);
    }

    /** @test */
    public function it_blocks_conversion_without_mapping()
    {
        // Clear mapping from product
        $this->mProduct->update([
            'product_id' => null,
            'product_combination_id' => null,
        ]);

        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job = $batch->jobs->first();

        // Complete final task job
        $workflowService->completeJob($job->id);
        $batch->refresh();

        $conversionService = new \App\Services\Manufacturing\FinishedGoodsConversionService();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no mapped Storefront Product/Variant');
        $conversionService->convertBatchToFinishedGoods($batch);
    }

    /** @test */
    public function it_creates_inventory_movement_on_successful_conversion()
    {
        $workflowService = new ProductionWorkflowService();
        $initResponse = $workflowService->initiateBatch($this->mProduct->id, $this->admin->id, 50);
        $initData = $initResponse->getData(true)['data'];

        $batch = ProductionBatch::find($initData['batch']['id']);
        $job = $batch->jobs->first();

        // Complete final task job
        $workflowService->completeJob($job->id);
        $batch->refresh();

        $conversionService = new \App\Services\Manufacturing\FinishedGoodsConversionService();
        $conversionService->convertBatchToFinishedGoods($batch, [
            'productId' => $this->storefrontProduct->id,
            'targetWarehouse' => 'Test Warehouse',
            'lotNumber' => 'LOT-TEST-123',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->storefrontProduct->id,
            'quantity_change' => 50,
            'movement_type' => 'manufacturing_inward',
            'reference_type' => ProductionBatch::class,
            'reference_id' => $batch->id,
        ]);
    }
}
