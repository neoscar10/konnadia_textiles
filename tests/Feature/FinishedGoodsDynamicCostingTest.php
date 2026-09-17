<?php

namespace Tests\Feature;

use App\Livewire\Admin\Production\FinishedGoodsConversionHub;
use App\Models\Category;
use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsBatchItem;
use App\Models\FinishedGoodsBatchPackaging;
use App\Models\FrontEndProduct;
use App\Models\FrontEndProductComponent;
use App\Models\InventoryBatch;
use App\Models\JobMaterialConsumption;
use App\Models\ManufacturingProduct;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinishedGoodsDynamicCostingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Category $leafCategory;
    protected ManufacturingProduct $mfgProduct;
    protected FrontEndProduct $feProduct;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access production');

        $this->leafCategory = Category::create([
            'name'                   => 'Bed Sheets',
            'slug'                   => 'bed-sheets',
            'is_leaf'                => true,
            'is_active'              => true,
            'default_product_config' => ['product_type' => 'manufactured'],
        ]);

        $this->mfgProduct = ManufacturingProduct::create([
            'name'   => 'KTC Bed Sheet 180TC',
            'code'   => 'MP-BED-001',
            'status' => 'active',
        ]);

        $this->feProduct = FrontEndProduct::create([
            'name'               => 'Bed Sheets',
            'sku'                => 'CAT-CFG-' . str_pad($this->leafCategory->id, 4, '0', STR_PAD_LEFT),
            'category_id'        => $this->leafCategory->id,
            'leaf_category_name' => 'Bed Sheets',
            'is_active'          => true,
        ]);

        FrontEndProductComponent::create([
            'front_end_product_id'     => $this->feProduct->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity'                 => 1,
        ]);
    }

    public function test_finished_goods_batch_calculates_dynamic_costing_summary_from_constituent_job(): void
    {
        // 1. Setup raw materials & inventory batches
        $catFab = RawMaterialCategory::create([
            'name' => 'Fabric', 'code' => 'CAT-FAB', 'unit_type' => 'length_based', 'is_active' => true
        ]);
        $catSub = RawMaterialCategory::create([
            'name' => 'Subsidiary', 'code' => 'CAT-SUB', 'unit_type' => 'other', 'is_active' => true
        ]);
        $catPkg = RawMaterialCategory::create([
            'name' => 'Packaging', 'code' => 'CAT-PKG', 'unit_type' => 'other', 'is_active' => true
        ]);

        $fabricMat = RawMaterial::create([
            'name' => 'Cotton Greige', 'code' => 'RM-FAB-001', 'raw_material_category_id' => $catFab->id, 'unit' => 'M', 'unit_cost' => 100.00, 'is_active' => true
        ]);
        $subMat = RawMaterial::create([
            'name' => 'Thread Roll', 'code' => 'RM-SUB-001', 'raw_material_category_id' => $catSub->id, 'unit' => 'Pcs', 'unit_cost' => 20.00, 'is_active' => true
        ]);
        $pkgMat = RawMaterial::create([
            'name' => 'Poly Bag Premium', 'code' => 'RM-PKG-001', 'raw_material_category_id' => $catPkg->id, 'unit' => 'Pcs', 'unit_cost' => 5.00, 'is_active' => true
        ]);

        $fabBatch = InventoryBatch::create([
            'raw_material_id' => $fabricMat->id, 'batch_number' => 'BAT-FAB-01', 'quantity_received' => 100, 'balance_quantity' => 100, 'unit_cost' => 100.00
        ]);
        $subBatch = InventoryBatch::create([
            'raw_material_id' => $subMat->id, 'batch_number' => 'BAT-SUB-01', 'quantity_received' => 50, 'balance_quantity' => 50, 'unit_cost' => 20.00
        ]);
        $pkgBatch = InventoryBatch::create([
            'raw_material_id' => $pkgMat->id, 'batch_number' => 'BAT-PKG-01', 'quantity_received' => 100, 'balance_quantity' => 100, 'unit_cost' => 5.00
        ]);

        // 2. Production Job (10 target units)
        $job = ProductionJob::create([
            'job_code'                 => 'JOB-COST-001',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity'          => 10,
            'converted_quantity'       => 0,
            'status'                   => 'completed',
        ]);

        // Job Consumptions: Fabric ₹1,000, Subsidiary ₹200
        JobMaterialConsumption::create([
            'production_job_id'  => $job->id,
            'job_code'           => $job->job_code,
            'inventory_batch_id' => $fabBatch->id,
            'quantity_consumed'  => 10,
            'unit_cost'          => 100.00,
            'total_cost'         => 1000.00,
            'consumed_length'    => 10,
        ]);

        JobMaterialConsumption::create([
            'production_job_id'  => $job->id,
            'job_code'           => $job->job_code,
            'inventory_batch_id' => $subBatch->id,
            'quantity_consumed'  => 10,
            'unit_cost'          => 20.00,
            'total_cost'         => 200.00,
        ]);

        // 3. Finished Goods Batch
        $fgBatch = FinishedGoodsBatch::create([
            'barcode'              => 'FG-COST-2026-0001',
            'front_end_product_id' => $this->feProduct->id,
            'design_id'            => 'COST01',
            'converted_qty'        => 10,
            'is_published'         => true,
            'converted_date'       => now(),
            'created_by'           => $this->admin->id,
        ]);

        FinishedGoodsBatchItem::create([
            'finished_goods_batch_id'  => $fgBatch->id,
            'production_job_id'        => $job->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity_used'            => 10,
        ]);

        // Deduct 10 packaging bags (₹5 each = ₹50 total)
        FinishedGoodsBatchPackaging::create([
            'finished_goods_batch_id' => $fgBatch->id,
            'raw_material_id'         => $pkgMat->id,
            'quantity_deducted'       => 10,
        ]);

        $summary = $fgBatch->calculateDynamicCostingSummary();

        $this->assertTrue($summary['is_dynamic']);
        $this->assertEquals(100.00, $summary['raw_values']['fabric']);
        $this->assertEquals(20.00, $summary['raw_values']['subsidiary']);
        $this->assertEquals(5.00, $summary['raw_values']['packaging']);
        $this->assertEquals(100.00, $summary['raw_values']['wastage']);
        $this->assertEquals(225.00, $summary['raw_values']['total']);
        $this->assertEquals('₹225.00', $summary['totalUnitCost']);
    }

    public function test_audit_modal_in_conversion_hub_renders_job_details_link(): void
    {
        $job = ProductionJob::create([
            'job_code'                 => 'JOB-LINK-999',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity'          => 5,
            'status'                   => 'completed',
        ]);

        $fgBatch = FinishedGoodsBatch::create([
            'barcode'              => 'FG-LINK-2026-9999',
            'front_end_product_id' => $this->feProduct->id,
            'design_id'            => 'LINK01',
            'converted_qty'        => 5,
            'is_published'         => true,
            'converted_date'       => now(),
            'created_by'           => $this->admin->id,
        ]);

        FinishedGoodsBatchItem::create([
            'finished_goods_batch_id'  => $fgBatch->id,
            'production_job_id'        => $job->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'quantity_used'            => 5,
        ]);

        Livewire::actingAs($this->admin)
            ->test(FinishedGoodsConversionHub::class)
            ->call('openAuditModal', $fgBatch->id)
            ->assertSet('showAuditModal', true)
            ->assertSee('JOB-LINK-999')
            ->assertSee(route('admin.production.jobs.show', $job->id))
            ->assertSee('open_in_new')
            ->assertSee('Unit Costing Breakdown (Per Converted Unit)');
    }
}
