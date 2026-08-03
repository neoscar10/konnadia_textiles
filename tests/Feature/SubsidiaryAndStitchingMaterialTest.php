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
use App\Models\StitchingCostPool;
use App\Services\Manufacturing\ProductionCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SubsidiaryAndStitchingMaterialTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProduct $product;
    protected ManufacturingProductCategory $category;
    protected RawMaterialCategory $catSub;
    protected RawMaterialCategory $catStitch;
    protected RawMaterial $buttonMaterial;
    protected RawMaterial $threadMaterial;
    protected InventoryBatch $buttonBatch;
    protected InventoryBatch $threadBatch;
    protected Task $subsidaryTask;
    protected ProductionBatch $prodBatch;
    protected ProductionJob $prodJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        // Create admin user with necessary role
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create raw material categories
        $this->catSub = RawMaterialCategory::create(['name' => 'Subsidiary Raw Material', 'code' => 'CAT-SUB']);
        $this->catStitch = RawMaterialCategory::create(['name' => 'Stitching', 'code' => 'CAT-STITCH']);

        // Create product category
        $this->category = ManufacturingProductCategory::create(['name' => 'Bedsheets', 'code' => 'CAT-001', 'status' => true]);

        // Create raw materials
        $this->buttonMaterial = RawMaterial::create([
            'raw_material_category_id' => $this->catSub->id,
            'name' => 'Buttons',
            'code' => 'RM-SUB-001',
            'unit' => 'Pieces',
        ]);

        $this->threadMaterial = RawMaterial::create([
            'raw_material_category_id' => $this->catStitch->id,
            'name' => 'Stitching Thread',
            'code' => 'RM-STITCH-001',
            'unit' => 'Cone',
        ]);

        // Create inventory batches
        $this->buttonBatch = InventoryBatch::create([
            'raw_material_id' => $this->buttonMaterial->id,
            'batch_number' => 'BT-SUB-001',
            'received_quantity' => 1000,
            'balance_quantity' => 1000,
            'unit' => 'Pieces',
            'unit_cost' => 0.50,
        ]);

        $this->threadBatch = InventoryBatch::create([
            'raw_material_id' => $this->threadMaterial->id,
            'batch_number' => 'BT-STITCH-001',
            'received_quantity' => 50,
            'balance_quantity' => 50,
            'unit' => 'Cone',
            'unit_cost' => 120.00,
        ]);

        // Create manufacturing product
        $this->product = ManufacturingProduct::create([
            'name' => 'Test Bedsheet',
            'code' => 'MP-2026-0001',
            'manufacturing_product_category_id' => $this->category->id,
            'status' => 'active',
            'standard_labor_rate' => 15.00,
            'is_subsidiary_used' => false,
            'is_stitching_used' => false,
        ]);

        // Task linked to CAT-SUB
        $this->subsidaryTask = Task::create(['name' => 'Assembly', 'code' => 'TSK-ASSEMBLY', 'status' => true]);
        $this->subsidaryTask->rawMaterialCategories()->attach($this->catSub->id);

        // Production setup
        $this->prodBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-STITCH-001',
            'status' => 'scheduled',
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 100,
        ]);

        $this->prodJob = ProductionJob::create([
            'job_code' => 'JOB-2026-STITCH-001',
            'production_batch_id' => $this->prodBatch->id,
            'manufacturing_product_id' => $this->product->id,
            'target_quantity' => 100,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_saves_subsidiary_materials_bom_with_auto_derived_unit()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Factory\AddManufacturingProductForm::class, ['id' => $this->product->id])
            ->set('is_subsidiary_used', true)
            ->set('subsidiaryMaterialsList', [
                [
                    'raw_material_id' => (string)$this->buttonMaterial->id,
                    'consumption_quantity' => '4.0000',
                    'unit' => 'Pieces', // auto-derived from raw material master
                ],
            ])
            ->call('save');

        $this->product->refresh();
        $this->assertTrue((bool)$this->product->is_subsidiary_used);

        $linked = $this->product->subsidiaryMaterials()->first();
        $this->assertNotNull($linked, 'Subsidiary material should be linked via pivot.');
        $this->assertEquals($this->buttonMaterial->id, $linked->id);
        $this->assertEquals('4.0000', $linked->pivot->consumption_quantity);
    }

    /** @test */
    public function it_saves_stitching_materials_without_consumption_quantities()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Factory\AddManufacturingProductForm::class, ['id' => $this->product->id])
            ->set('is_stitching_used', true)
            ->set('stitchingMaterialsList', [(string)$this->threadMaterial->id])
            ->call('save');

        $this->product->refresh();
        $this->assertTrue((bool)$this->product->is_stitching_used);

        $linked = $this->product->stitchingMaterials()->first();
        $this->assertNotNull($linked, 'Stitching material should be linked via pivot.');
        $this->assertEquals($this->threadMaterial->id, $linked->id);
        // Confirm there's NO consumption_quantity column on stitching pivot (SRS: no per-piece qty)
        $columns = array_keys((array)$linked->pivot->getAttributes());
        $this->assertNotContains('consumption_quantity', $columns);
    }

    /** @test */
    public function it_deducts_subsidiary_stock_without_wastage_on_save()
    {
        $this->actingAs($this->admin);

        // Configure product BOM: 4 buttons per piece, 100 target = 400 expected
        $this->product->subsidiaryMaterials()->sync([
            $this->buttonMaterial->id => ['consumption_quantity' => '4.0000'],
        ]);
        $this->product->update(['is_subsidiary_used' => true]);

        $initialStock = (float) $this->buttonBatch->balance_quantity;

        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $this->prodJob->id])
            ->call('selectTask', $this->subsidaryTask->id)
            ->set('subsidiaryConsumptions.0.inventory_batch_id', $this->buttonBatch->id)
            ->set('subsidiaryConsumptions.0.actual_consumed', '400')
            ->call('saveSubsidiaryConsumption');

        $this->buttonBatch->refresh();
        $this->assertEquals($initialStock - 400, (float)$this->buttonBatch->balance_quantity, 'Stock should decrease by actual consumed (400 Pieces).');

        // Verify cost logged: 400 * 0.50 = 200.00
        $consumption = JobMaterialConsumption::where('production_job_id', $this->prodJob->id)->first();
        $this->assertNotNull($consumption);
        $this->assertEquals(400, (float)$consumption->quantity_consumed);
        $this->assertEquals(200.00, (float)$consumption->total_cost);
    }

    /** @test */
    public function it_prevents_subsidiary_consumption_exceeding_stock()
    {
        $this->actingAs($this->admin);

        $this->product->subsidiaryMaterials()->sync([
            $this->buttonMaterial->id => ['consumption_quantity' => '4.0000'],
        ]);
        $this->product->update(['is_subsidiary_used' => true]);

        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $this->prodJob->id])
            ->call('selectTask', $this->subsidaryTask->id)
            ->set('subsidiaryConsumptions.0.inventory_batch_id', $this->buttonBatch->id)
            ->set('subsidiaryConsumptions.0.actual_consumed', '9999')  // exceeds 1000 stock
            ->call('saveSubsidiaryConsumption')
            ->assertHasErrors(['subsidiaryConsumptions.0.actual_consumed']);
    }

    /** @test */
    public function it_accumulates_stitching_costs_into_periodic_cost_pool()
    {
        $service = new ProductionCostingService();

        // Simulate stitching material being received (creates a batch)
        // already created in setUp: threadBatch (50 Cones @ ₹120 = ₹6000 total)

        $pool = $service->accumulateStitchingCostPool(
            now()->startOfMonth(),
            now()->endOfMonth(),
            'August 2026'
        );

        $this->assertInstanceOf(StitchingCostPool::class, $pool);
        $this->assertEquals('August 2026', $pool->period_name);
        $this->assertEquals('open', $pool->status);

        // Total should include the batch purchase value: 50 * 120 = 6000
        $this->assertEquals(6000.00, (float)$pool->total_stitching_cost);
    }

    /** @test */
    public function it_excludes_stitching_materials_from_general_inventory_picker()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $this->prodJob->id]);

        // Thread batch (CAT-STITCH) should NOT appear in the general available batches
        $batchIds = $component->get('availableBatches')->pluck('id')->toArray();
        $this->assertNotContains($this->threadBatch->id, $batchIds, 'CAT-STITCH batches must not appear in the general inventory picker.');
    }
}
