<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\ProductionBatch;
use App\Livewire\Factory\CuttingStageWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BaleFabricFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterial $fabric1;
    protected RawMaterial $fabric2;
    protected InventoryBatch $batch1;
    protected InventoryBatch $batch2;
    protected InventoryBale $bale1;
    protected InventoryBale $bale2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $rmCat = RawMaterialCategory::create(['name' => 'Fabrics', 'code' => 'CAT-FAB']);

        $this->fabric1 = RawMaterial::create([
            'name' => 'Cotton Silk Blue',
            'code' => 'RM-FAB-BLUE',
            'raw_material_category_id' => $rmCat->id,
            'standard_width' => 60.0,
            'unit' => 'Meters',
            'status' => 'active',
        ]);

        $this->fabric2 = RawMaterial::create([
            'name' => 'Linen White',
            'code' => 'RM-FAB-WHITE',
            'raw_material_category_id' => $rmCat->id,
            'standard_width' => 90.0,
            'unit' => 'Meters',
            'status' => 'active',
        ]);

        $this->batch1 = InventoryBatch::create([
            'batch_number' => 'BAT-BLUE-001',
            'raw_material_id' => $this->fabric1->id,
            'received_quantity' => 500,
            'balance_quantity' => 500,
            'unit_cost' => 10.0,
        ]);

        $this->bale1 = InventoryBale::create([
            'inventory_batch_id' => $this->batch1->id,
            'bale_number' => 'BALE-BLUE-001',
            'declared_length' => 500,
            'current_balance_length' => 500,
            'status' => 'opened',
        ]);

        $this->batch2 = InventoryBatch::create([
            'batch_number' => 'BAT-WHITE-002',
            'raw_material_id' => $this->fabric2->id,
            'received_quantity' => 300,
            'balance_quantity' => 300,
            'unit_cost' => 12.0,
        ]);

        $this->bale2 = InventoryBale::create([
            'inventory_batch_id' => $this->batch2->id,
            'bale_number' => 'BALE-WHITE-002',
            'declared_length' => 300,
            'current_balance_length' => 300,
            'status' => 'opened',
        ]);
    }

    /** @test */
    public function selecting_bale_filters_fabric_materials_to_only_those_in_selected_bale()
    {
        $this->actingAs($this->admin);

        $pBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-0041',
            'status' => 'In Cutting',
            'planned_quantity' => 0,
        ]);

        $test = Livewire::test(CuttingStageWizard::class, ['batch' => $pBatch->batch_code]);

        // 1. When no bale is selected, getAvailableFabricsForBaleOrBatch returns all active fabrics
        $allFabrics = $test->instance()->getAvailableFabricsForBaleOrBatch();
        $this->assertCount(2, $allFabrics);

        // 2. When bale1 (Cotton Silk Blue) is selected for row 0
        $test->set('selectedFabrics.0.inventory_bale_id', $this->bale1->id);

        $filteredForBale1 = $test->instance()->getAvailableFabricsForBaleOrBatch($this->bale1->id);
        $this->assertCount(1, $filteredForBale1);
        $this->assertEquals($this->fabric1->id, $filteredForBale1->first()->id);

        // Raw material ID should be auto-prefilled to fabric1
        $test->assertSet('selectedFabrics.0.raw_material_id', (string) $this->fabric1->id);

        // 3. When bale2 (Linen White) is selected for row 0
        $test->set('selectedFabrics.0.inventory_bale_id', $this->bale2->id);

        $filteredForBale2 = $test->instance()->getAvailableFabricsForBaleOrBatch($this->bale2->id);
        $this->assertCount(1, $filteredForBale2);
        $this->assertEquals($this->fabric2->id, $filteredForBale2->first()->id);

        // Raw material ID should be auto-prefilled to fabric2
        $test->assertSet('selectedFabrics.0.raw_material_id', (string) $this->fabric2->id);
    }
}
