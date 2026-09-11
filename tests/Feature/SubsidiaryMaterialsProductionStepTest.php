<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\JobMaterialConsumption;
use App\Services\Manufacturing\ProductionCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubsidiaryMaterialsProductionStepTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $category;
    protected RawMaterialCategory $subCategory;
    protected RawMaterial $buttonMaterial;
    protected RawMaterial $zipperMaterial;
    protected InventoryBatch $buttonBatch;
    protected InventoryBatch $zipperBatch;
    protected ManufacturingProduct $product;
    protected Task $finalTask;
    protected ProductionBatch $prodBatch;
    protected ProductionJob $prodJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->subCategory = RawMaterialCategory::create(['name' => 'Subsidiary Raw Material', 'code' => 'CAT-SUB']);
        $this->category = ManufacturingProductCategory::create(['name' => 'Bedsheets', 'code' => 'CAT-001', 'status' => true]);

        $this->buttonMaterial = RawMaterial::create([
            'raw_material_category_id' => $this->subCategory->id,
            'name' => 'Buttons',
            'code' => 'RM-SUB-BT',
            'unit' => 'Pieces',
        ]);

        $this->zipperMaterial = RawMaterial::create([
            'raw_material_category_id' => $this->subCategory->id,
            'name' => 'Zippers',
            'code' => 'RM-SUB-ZP',
            'unit' => 'Pieces',
        ]);

        $this->buttonBatch = InventoryBatch::create([
            'raw_material_id' => $this->buttonMaterial->id,
            'batch_number' => 'BT-SUB-001',
            'received_quantity' => 1000,
            'balance_quantity' => 1000,
            'unit' => 'Pieces',
            'unit_cost' => 0.50,
            'purchase_rate' => 0.50,
        ]);

        $this->zipperBatch = InventoryBatch::create([
            'raw_material_id' => $this->zipperMaterial->id,
            'batch_number' => 'BT-SUB-002',
            'received_quantity' => 500,
            'balance_quantity' => 500,
            'unit' => 'Pieces',
            'unit_cost' => 2.00,
            'purchase_rate' => 2.00,
        ]);

        $this->product = ManufacturingProduct::create([
            'name' => 'Premium Bedsheet',
            'code' => 'MP-2026-SUB-01',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'is_subsidiary_used' => true,
        ]);

        // Attach subsidiary materials to product BOM: 2 buttons, 1 zipper per product unit
        $this->product->subsidiaryMaterials()->sync([
            $this->buttonMaterial->id => ['consumption_quantity' => 2.0],
            $this->zipperMaterial->id => ['consumption_quantity' => 1.0],
        ]);

        // Create routing task as final step
        $this->finalTask = Task::create(['name' => 'Packing & Final Assembly', 'code' => 'TSK-FINAL', 'status' => true]);
        $this->product->tasks()->attach($this->finalTask->id, ['sequence_number' => 1, 'is_final_step' => true]);

        $this->prodBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-SUB-TEST',
            'status' => 'scheduled',
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 10,
        ]);

        $this->prodJob = ProductionJob::create([
            'job_code' => 'JOB-2026-SUB-TEST',
            'production_batch_id' => $this->prodBatch->id,
            'production_batch_db_id' => $this->prodBatch->id,
            'manufacturing_product_id' => $this->product->id,
            'target_quantity' => 10,
            'status' => 'in_progress',
        ]);
        $this->prodJob->ensureStageExecutionsExist();
    }

    /** @test */
    public function it_calculates_subsidiary_material_standard_and_extra_quantities()
    {
        $this->actingAs($this->admin);

        // Test JobStageWizard component initializes subsidiary rows for output = 10 units
        $component = Livewire::test(\App\Livewire\Factory\JobStageWizard::class, ['id' => $this->prodJob->id])
            ->set('producedQty', 10);

        $subsidiaryRows = $component->get('subsidiaryRows');
        $this->assertCount(2, $subsidiaryRows, 'Should contain 2 subsidiary material rows from BOM.');

        // Buttons: 2 per unit x 10 units = 20 Pcs @ $0.50 = $10.00
        $buttonRow = collect($subsidiaryRows)->firstWhere('raw_material_id', $this->buttonMaterial->id);
        $this->assertEquals(2.0, (float)$buttonRow['bom_per_unit']);
        $this->assertEquals(20.0, (float)$buttonRow['std_req_qty']);
        $this->assertEquals(20.0, (float)$buttonRow['total_qty']);
        $this->assertEquals(10.00, (float)$buttonRow['total_cost']);

        // Zippers: 1 per unit x 10 units = 10 Pcs @ $2.00 = $20.00
        $zipperRow = collect($subsidiaryRows)->firstWhere('raw_material_id', $this->zipperMaterial->id);
        $this->assertEquals(1.0, (float)$zipperRow['bom_per_unit']);
        $this->assertEquals(10.0, (float)$zipperRow['std_req_qty']);
        $this->assertEquals(10.0, (float)$zipperRow['total_qty']);
        $this->assertEquals(20.00, (float)$zipperRow['total_cost']);
    }

    /** @test */
    public function it_saves_subsidiary_material_consumption_including_extra_qty_and_deducts_stock()
    {
        $this->actingAs($this->admin);

        $labor = \App\Models\Labor::create([
            'name' => 'Packer Worker',
            'worker_code' => 'W-PACK-01',
            'daily_rate' => 450,
            'piece_rate' => 5,
            'status' => 'active',
        ]);

        // Set extra 5 buttons damaged/extra
        Livewire::test(\App\Livewire\Factory\JobStageWizard::class, ['id' => $this->prodJob->id])
            ->set('laborRows.0.labor_id', $labor->id)
            ->set('producedQty', 10)
            ->set('subsidiaryRows.0.extra_qty', 5) // Button total: (2*10)+5 = 25 Pcs
            ->call('completeActiveStage');

        // Check Button Inventory Batch Stock: 1000 - 25 = 975
        $this->buttonBatch->refresh();
        $this->assertEquals(975, (float)$this->buttonBatch->balance_quantity);

        // Check Zipper Inventory Batch Stock: 500 - 10 = 490
        $this->zipperBatch->refresh();
        $this->assertEquals(490, (float)$this->zipperBatch->balance_quantity);

        // Verify JobMaterialConsumption records
        $buttonConsumption = JobMaterialConsumption::where('production_job_id', $this->prodJob->id)
            ->where('inventory_batch_id', $this->buttonBatch->id)
            ->first();

        $this->assertNotNull($buttonConsumption);
        $this->assertEquals(25, (float)$buttonConsumption->quantity_consumed);
        $this->assertEquals(0.50, (float)$buttonConsumption->unit_cost);
        $this->assertEquals(12.50, (float)$buttonConsumption->total_cost); // 25 * 0.50 = 12.50

        // Check batch cost summary rollup in ProductionCostingService
        $costingService = new ProductionCostingService();
        $batchSummary = $costingService->getBatchCostSummary($this->prodBatch->id);

        // Button cost ($12.50) + Zipper cost ($20.00) = $32.50
        $this->assertEquals(32.50, (float)$batchSummary['subsidiary_cost']);
    }
}
