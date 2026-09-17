<?php

namespace Tests\Unit;

use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsBatchPackaging;
use App\Models\FrontEndProduct;
use App\Models\InventoryBatch;
use App\Models\JobLaborAllocation;
use App\Models\JobMaterialConsumption;
use App\Models\JobWastage;
use App\Models\Labor;
use App\Models\ManufacturingProduct;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Services\Manufacturing\MonthlyProductionValueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyProductionValueServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MonthlyProductionValueService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MonthlyProductionValueService();
    }

    /** @test */
    public function it_calculates_production_value_excluding_salaried_labor_and_including_packaging()
    {
        $year = 2026;
        $month = 5;
        $periodDate = Carbon::create($year, $month, 10);

        // 1. Categories
        $catFab = RawMaterialCategory::create([
            'name' => 'Fabrics', 'code' => 'CAT-FAB', 'unit_type' => 'length_based', 'is_active' => true
        ]);
        $catSub = RawMaterialCategory::create([
            'name' => 'Trims and Subsidiaries', 'code' => 'CAT-SUB', 'unit_type' => 'other', 'is_active' => true
        ]);
        $catPkg = RawMaterialCategory::create([
            'name' => 'Packaging Material', 'code' => 'CAT-PKG', 'unit_type' => 'other', 'is_active' => true
        ]);

        // 2. Raw Materials
        $fabricMat = RawMaterial::create([
            'name' => 'Cotton Cambric Fabric', 'code' => 'RM-FAB-001', 'raw_material_category_id' => $catFab->id, 'unit' => 'Meters', 'unit_cost' => 120.00, 'is_active' => true
        ]);
        $subMat = RawMaterial::create([
            'name' => 'Shirt Button 12mm', 'code' => 'RM-SUB-001', 'raw_material_category_id' => $catSub->id, 'unit' => 'Pcs', 'unit_cost' => 2.50, 'is_active' => true
        ]);
        $pkgMat = RawMaterial::create([
            'name' => 'Polybag Premium 10x12', 'code' => 'RM-PKG-001', 'raw_material_category_id' => $catPkg->id, 'unit' => 'Pcs', 'unit_cost' => 5.00, 'is_active' => true
        ]);

        // Inventory Batches
        $fabBatch = InventoryBatch::create([
            'raw_material_id' => $fabricMat->id, 'batch_number' => 'BAT-FAB-1', 'quantity_received' => 500, 'balance_quantity' => 500, 'unit_cost' => 120.00
        ]);
        $subBatch = InventoryBatch::create([
            'raw_material_id' => $subMat->id, 'batch_number' => 'BAT-SUB-1', 'quantity_received' => 1000, 'balance_quantity' => 1000, 'unit_cost' => 2.50
        ]);
        $pkgBatch = InventoryBatch::create([
            'raw_material_id' => $pkgMat->id, 'batch_number' => 'BAT-PKG-1', 'quantity_received' => 200, 'balance_quantity' => 200, 'unit_cost' => 5.00
        ]);

        // 3. Labors: Piece-Rate vs Salaried
        $pieceWorker = Labor::create([
            'name' => 'Ali Pieceworker', 'mobile_number' => '9991112221', 'payment_method' => 'job_work', 'monthly_salary' => 0.00, 'status' => true
        ]);
        $salariedStaff = Labor::create([
            'name' => 'Sunil Salaried Master', 'mobile_number' => '9991112222', 'payment_method' => 'salary', 'monthly_salary' => 25000.00, 'status' => true
        ]);

        // 4. Manufacturing Product & Completed Production Job
        $mfgProduct = ManufacturingProduct::create([
            'name' => 'Men Formal Shirt', 'code' => 'MFG-SHIRT-01', 'status' => 'active'
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-0001',
            'manufacturing_product_id' => $mfgProduct->id,
            'job_date' => $periodDate->format('Y-m-d'),
            'target_quantity' => 100,
            'converted_quantity' => 0,
            'status' => 'completed',
        ]);

        // Job Consumptions
        // Fabric: 100m * 120 = 12000
        JobMaterialConsumption::create([
            'production_job_id' => $job->id,
            'job_code' => $job->job_code,
            'inventory_batch_id' => $fabBatch->id,
            'quantity_consumed' => 100,
            'unit_cost' => 120.00,
            'total_cost' => 12000.00,
            'consumed_length' => 100,
        ]);

        // Subsidiary: 800 buttons * 2.50 = 2000
        JobMaterialConsumption::create([
            'production_job_id' => $job->id,
            'job_code' => $job->job_code,
            'inventory_batch_id' => $subBatch->id,
            'quantity_consumed' => 800,
            'unit_cost' => 2.50,
            'total_cost' => 2000.00,
        ]);

        $task = \App\Models\Task::create([
            'name' => 'Cutting and Stitching',
            'status' => true,
        ]);

        // Wastage: 2 Defective pieces worth 300
        JobWastage::create([
            'production_job_id' => $job->id,
            'job_code' => $job->job_code,
            'task_id' => $task->id,
            'quantity_wasted' => 2,
            'reason' => 'Defective collar cutting',
        ]);
        // Job direct wastage will use default fabric rate = 120 * 2 = 240.00

        // Labor Allocations
        // Piece-rate: ₹1,500.00 (MUST BE INCLUDED)
        JobLaborAllocation::create([
            'job_id' => $job->job_code,
            'task_id' => $task->id,
            'labor_id' => $pieceWorker->id,
            'quantity_processed' => 100,
            'base_rate' => 15.00,
            'calculated_wage' => 1500.00,
        ]);

        // Salaried staff: ₹3,000.00 (MUST BE EXCLUDED)
        JobLaborAllocation::create([
            'job_id' => $job->job_code,
            'task_id' => $task->id,
            'labor_id' => $salariedStaff->id,
            'quantity_processed' => 100,
            'base_rate' => 30.00,
            'calculated_wage' => 3000.00,
        ]);

        // Expected Job Cost without salaried labor:
        // Fabric (12,000) + Subsidiary (2,000) + Wastage (240) + PieceRateLabor (1,500) = 15,740.00

        // 5. Storefront Conversion (Finished Goods Batch with Packaging)
        $feProduct = FrontEndProduct::create([
            'name' => 'Storefront Premium Shirt', 'sku' => 'SKU-SHIRT-01', 'is_active' => true
        ]);

        $fgBatch = FinishedGoodsBatch::create([
            'barcode' => 'FG-2026-0001',
            'front_end_product_id' => $feProduct->id,
            'converted_qty' => 100,
            'converted_date' => $periodDate,
            'is_published' => true,
        ]);

        // Packaging: 100 polybags * ₹5.00 = ₹500.00
        FinishedGoodsBatchPackaging::create([
            'finished_goods_batch_id' => $fgBatch->id,
            'raw_material_id' => $pkgMat->id,
            'quantity_deducted' => 100,
        ]);

        // Run calculation
        $result = $this->service->calculate($year, $month);

        // Assertions
        $this->assertEquals(1, $result['completed_jobs_count']);
        $this->assertEquals(1, $result['conversions_count']);
        $this->assertEquals(12000.00, $result['total_fabric_cost']);
        $this->assertEquals(2000.00, $result['total_subsidiary_cost']);
        $this->assertEquals(240.00, $result['total_wastage_cost']);
        $this->assertEquals(1500.00, $result['total_piece_rate_labor']);
        $this->assertEquals(3000.00, $result['total_salaried_labor_excluded']);
        $this->assertEquals(15740.00, $result['total_jobs_cost']);
        $this->assertEquals(500.00, $result['total_packaging_cost']);

        // Grand Total Production Value: 15,740.00 + 500.00 = 16,240.00
        $this->assertEquals(16240.00, $result['total_production_value']);
    }

    /** @test */
    public function it_ignores_jobs_and_conversions_outside_the_selected_month()
    {
        $year = 2026;
        $month = 5;

        // Job in April 2026
        $mfgProduct = ManufacturingProduct::create([
            'name' => 'Sample', 'code' => 'MFG-SMP', 'status' => 'active'
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-2026-APRIL',
            'manufacturing_product_id' => $mfgProduct->id,
            'job_date' => '2026-04-15',
            'target_quantity' => 50,
            'status' => 'completed',
        ]);

        // Job in May 2026 but incomplete (pending)
        ProductionJob::create([
            'job_code' => 'JOB-2026-PENDING',
            'manufacturing_product_id' => $mfgProduct->id,
            'job_date' => '2026-05-15',
            'target_quantity' => 50,
            'status' => 'in_progress',
        ]);

        $result = $this->service->calculate($year, $month);

        $this->assertEquals(0, $result['completed_jobs_count']);
        $this->assertEquals(0.00, $result['total_production_value']);
    }
}
