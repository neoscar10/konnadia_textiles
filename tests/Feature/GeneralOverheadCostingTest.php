<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Models\Product;
use App\Services\Manufacturing\ProductionCostingService;
use App\Services\Manufacturing\FinishedGoodsConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class GeneralOverheadCostingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $overheadCategory;
    protected RawMaterialCategory $packagingCategory;
    protected RawMaterial $oilMaterial;
    protected RawMaterial $boxMaterial;
    protected InventoryBatch $oilBatch1;
    protected InventoryBatch $oilBatch2;
    protected InventoryBatch $boxBatch;
    protected ManufacturingProductCategory $mpCategory;
    protected ManufacturingProduct $mProduct;
    protected ProductionBatch $prodBatch;
    protected ProductionJob $prodJob;
    protected Product $storefrontProduct;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super_admin');

        // Seed categories via seeder
        $this->seed(\Database\Seeders\RawMaterialCategorySeeder::class);

        $this->overheadCategory = RawMaterialCategory::where('code', 'CAT-OHD')->firstOrFail();
        $this->packagingCategory = RawMaterialCategory::where('code', 'CAT-PKG')->firstOrFail();

        // Create overhead raw material (Machine Oil)
        $this->oilMaterial = RawMaterial::create([
            'name' => 'Lubricant Oil',
            'code' => 'RAW-OHD-OIL',
            'raw_material_category_id' => $this->overheadCategory->id,
            'unit' => 'Liters',
            'is_active' => true,
        ]);

        // Create packaging raw material (Packaging Box)
        $this->boxMaterial = RawMaterial::create([
            'name' => 'Cardboard Box Large',
            'code' => 'RAW-PKG-BOX',
            'raw_material_category_id' => $this->packagingCategory->id,
            'unit' => 'Pieces',
            'is_active' => true,
        ]);

        // Create overhead inventory batches (FIFO order)
        $this->oilBatch1 = InventoryBatch::create([
            'raw_material_id' => $this->oilMaterial->id,
            'batch_number' => 'BAT-OHD-0001',
            'purchase_date' => '2026-05-01',
            'quantity_received' => 10.0,
            'balance_quantity' => 10.0,
            'purchase_rate' => 300.00, // ₹300/liter
            'unit' => 'Liters',
        ]);

        $this->oilBatch2 = InventoryBatch::create([
            'raw_material_id' => $this->oilMaterial->id,
            'batch_number' => 'BAT-OHD-0002',
            'purchase_date' => '2026-05-05',
            'quantity_received' => 20.0,
            'balance_quantity' => 20.0,
            'purchase_rate' => 320.00, // ₹320/liter
            'unit' => 'Liters',
        ]);

        // Create packaging inventory batch
        $this->boxBatch = InventoryBatch::create([
            'raw_material_id' => $this->boxMaterial->id,
            'batch_number' => 'BAT-PKG-0001',
            'purchase_date' => '2026-05-01',
            'quantity_received' => 100.0,
            'balance_quantity' => 100.0,
            'purchase_rate' => 15.00, // ₹15/piece
            'unit' => 'Pieces',
        ]);

        $this->mpCategory = ManufacturingProductCategory::create([
            'name' => 'Home Decor',
            'code' => 'CAT-002',
            'status' => true,
        ]);

        $this->mProduct = ManufacturingProduct::create([
            'name' => 'Luxury Cushion Cover',
            'code' => 'MP-2026-8001',
            'manufacturing_product_category_id' => $this->mpCategory->id,
            'status' => 'active',
            'standard_labor_rate' => 10.00,
            'is_packaging_used' => true,
        ]);

        // Link packaging material to product (1 box per item)
        $this->mProduct->packagingMaterials()->attach($this->boxMaterial->id, ['required_quantity' => 1.0000]);

        $this->finalTask = Task::create([
            'name' => 'Packaging Task',
            'code' => 'TSK-PKG',
            'status' => true,
        ]);

        $this->mProduct->tasks()->sync([
            $this->finalTask->id => ['sequence_number' => 1, 'standard_labor_rate' => 5.00, 'is_final_step' => true],
        ]);

        $this->prodBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-OHD-001',
            'status' => 'scheduled',
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->mProduct->id,
            'planned_quantity' => 10,
        ]);

        $this->prodJob = ProductionJob::create([
            'job_code' => 'JOB-2026-OHD-001',
            'production_batch_db_id' => $this->prodBatch->id,
            'manufacturing_product_id' => $this->mProduct->id,
            'target_quantity' => 10,
            'status' => 'pending',
        ]);

        $this->stageExecution = \App\Models\JobStageExecution::create([
            'production_job_id' => $this->prodJob->id,
            'task_id' => $this->finalTask->id,
            'sequence_number' => 1,
            'target_quantity' => 10,
            'status' => 'pending',
        ]);


        $this->storefrontProduct = Product::create([
            'title' => 'Luxury Cushion Cover Storefront',
            'sku' => 'LUX-CUSH-001',
            'base_price' => 200.00,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);
    }

    public function test_overhead_category_is_overhead()
    {
        $this->assertTrue($this->overheadCategory->isOverhead());
        $this->assertTrue($this->oilMaterial->isOverhead());
        $this->assertFalse($this->boxMaterial->isOverhead());
    }

    public function test_allocate_overhead_cost_deducts_via_fifo()
    {
        $costingService = new ProductionCostingService();

        // Allocate 15 Liters of Lubricant Oil to the batch
        // Since we allocate 15 Liters, it should deduct 10 Liters from oilBatch1 and 5 Liters from oilBatch2.
        $costingService->allocateOverheadCost($this->prodBatch, [
            [
                'raw_material_id' => $this->oilMaterial->id,
                'allocated_quantity' => 15.0,
                'allocation_method' => 'direct_batch',
            ]
        ]);

        $this->oilBatch1->refresh();
        $this->oilBatch2->refresh();

        // Oil batch 1 (10 liters) should be fully depleted
        $this->assertEquals(0.0, (float)$this->oilBatch1->balance_quantity);
        $this->assertEquals('depleted', $this->oilBatch1->status);

        // Oil batch 2 (20 liters) should have 15 liters remaining
        $this->assertEquals(15.0, (float)$this->oilBatch2->balance_quantity);
        $this->assertEquals('active', $this->oilBatch2->status);

        // Verify cost allocation logs
        $this->assertDatabaseHas('overhead_cost_allocations', [
            'production_batch_id' => $this->prodBatch->id,
            'raw_material_id' => $this->oilMaterial->id,
            'inventory_batch_id' => $this->oilBatch1->id,
            'allocated_quantity' => 10.0,
            'allocated_cost' => 3000.00, // 10 * 300
        ]);

        $this->assertDatabaseHas('overhead_cost_allocations', [
            'production_batch_id' => $this->prodBatch->id,
            'raw_material_id' => $this->oilMaterial->id,
            'inventory_batch_id' => $this->oilBatch2->id,
            'allocated_quantity' => 5.0,
            'allocated_cost' => 1600.00, // 5 * 320
        ]);

        // Verify summary calculations
        $summary = $costingService->getBatchCostSummary($this->prodBatch->id);
        // Total overhead cost = 3000 + 1600 = ₹4600
        $this->assertEquals(4600.00, $summary['overhead_cost']);
        $this->assertEquals(4600.00, $summary['total_material_cost']);
    }

    public function test_conversion_deducts_packaging_materials_via_fifo()
    {
        // 1. Complete final job & stage execution
        $this->stageExecution->update(['status' => 'completed']);
        $this->prodJob->update(['status' => 'completed']);
        $this->prodBatch->update(['status' => 'Completed']);

        // Check stock before conversion
        $this->assertEquals(100.0, (float)$this->boxBatch->balance_quantity);

        $conversionService = new FinishedGoodsConversionService();
        $conversionService->convertBatchToFinishedGoods($this->prodBatch, [
            'productId' => $this->storefrontProduct->id,
            'lotNumber' => 'LOT-CUSH-001',
            'targetWarehouse' => 'Packaging Warehouse',
            'packaging' => [
                ['raw_material_id' => $this->boxMaterial->id, 'quantity_used' => 10.0],
            ],
        ]);

        // Since planned_quantity = 10, and required_quantity = 1.0 per unit, it should deduct 10 boxes
        $this->boxBatch->refresh();
        $this->assertEquals(90.0, (float)$this->boxBatch->balance_quantity);

        // Total cost of packaging = 10 * ₹15 = ₹150.00
        // Check if packaging cost logged under JobMaterialConsumption
        $this->assertDatabaseHas('job_material_consumptions', [
            'production_job_id' => $this->prodJob->id,
            'inventory_batch_id' => $this->boxBatch->id,
            'quantity_consumed' => 10.0,
            'total_cost' => 150.00,
        ]);

        // Check if batch is converted and storefront product stock increased
        $this->prodBatch->refresh();
        $this->assertTrue($this->prodBatch->is_converted);

        $this->storefrontProduct->refresh();
        $this->assertEquals(15, $this->storefrontProduct->stock_quantity); // 5 + 10 = 15
    }
}
