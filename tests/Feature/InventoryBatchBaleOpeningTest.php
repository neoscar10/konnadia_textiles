<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Livewire\Factory\InventoryBatchList;
use App\Livewire\Factory\InventoryBatchDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class InventoryBatchBaleOpeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $fabricCategory;
    protected RawMaterial $fabric;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super_admin');

        $this->fabricCategory = RawMaterialCategory::create([
            'name' => 'Fabric',
            'code' => 'CAT-FAB',
            'unit_type' => 'length_based',
            'description' => 'Length-based fabric materials',
            'is_active' => true,
        ]);

        $this->fabric = RawMaterial::create([
            'name' => 'Premium Cotton Fabric',
            'code' => 'FAB-COT-01',
            'raw_material_category_id' => $this->fabricCategory->id,
            'unit' => 'Meters',
            'standard_width' => 58.00,
            'width_unit' => 'Inch',
            'is_active' => true,
        ]);
    }

    public function test_inventory_batch_list_shows_lot_number_and_bale_badges()
    {
        $this->actingAs($this->admin);

        $batch = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Textile Mills Ltd',
            'purchase_date' => '2026-09-10',
            'invoice_number' => 'INV-2026-999',
            'lot_number' => 'LOT-2026-088',
            'quantity_received' => 200,
            'balance_quantity' => 200,
            'purchase_rate' => 150,
            'total_amount' => 30000,
            'unit' => 'Meters',
            'num_bales' => 2,
            'status' => 'active',
        ]);

        $bale1 = $batch->bales()->create([
            'bale_number' => 'BALE-2026-0001',
            'item_name' => 'Premium Cotton Fabric',
            'design_number' => 'DSG-101',
            'stock_id' => 'STK-001',
            'status' => 'unopened',
            'declared_length' => 100,
            'current_balance_length' => 100,
            'cost_per_unit' => 150,
            'total_cost' => 15000,
        ]);

        Livewire::test(InventoryBatchList::class)
            ->assertSee('LOT-2026-088')
            ->assertSee('Textile Mills Ltd')
            ->assertSee('1 Bales');
    }

    public function test_inventory_batch_detail_shows_bales_and_items_from_purchase_entry()
    {
        $this->actingAs($this->admin);

        $batch = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Global Fabrics Ltd',
            'purchase_date' => '2026-09-10',
            'invoice_number' => 'INV-2026-888',
            'lot_number' => 'LOT-2026-077',
            'quantity_received' => 100,
            'balance_quantity' => 100,
            'purchase_rate' => 200,
            'total_amount' => 20000,
            'unit' => 'Meters',
            'num_bales' => 1,
            'status' => 'active',
        ]);

        $bale = $batch->bales()->create([
            'bale_number' => 'BALE-2026-0005',
            'item_name' => 'Premium Cotton Fabric',
            'design_number' => 'DSG-999',
            'stock_id' => 'STK-999',
            'status' => 'unopened',
            'declared_length' => 100,
            'current_balance_length' => 100,
            'cost_per_unit' => 200,
            'total_cost' => 20000,
        ]);

        $response = $this->get(route('factory.raw-materials.batches.show', ['batch' => $batch->id]));
        $response->assertStatus(200);
        $response->assertSee('BALE-2026-0005');
        $response->assertSee('DSG-999');
        $response->assertSee('STK-999');
        $response->assertSee('Open Bale');
    }

    public function test_can_open_bale_from_inventory_batch_detail_page()
    {
        $this->actingAs($this->admin);

        $batch = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Vardhman Mills',
            'purchase_date' => '2026-09-10',
            'invoice_number' => 'INV-2026-777',
            'lot_number' => 'LOT-2026-055',
            'quantity_received' => 100,
            'balance_quantity' => 100,
            'purchase_rate' => 100,
            'total_amount' => 10000,
            'unit' => 'Meters',
            'num_bales' => 1,
            'status' => 'active',
        ]);

        $bale = $batch->bales()->create([
            'bale_number' => 'BALE-2026-0010',
            'item_name' => 'Premium Cotton Fabric',
            'design_number' => 'DSG-555',
            'stock_id' => 'STK-555',
            'status' => 'unopened',
            'declared_length' => 100,
            'current_balance_length' => 100,
            'cost_per_unit' => 100,
            'total_cost' => 10000,
        ]);

        Livewire::test(InventoryBatchDetail::class, ['batch' => $batch])
            ->call('triggerOpenBaleModal', $bale->id)
            ->assertSet('showOpenBaleModal', true)
            ->set('baleRollCount', 2)
            ->set('baleRollLengths', [50.00, 50.00])
            ->call('submitOpenedBaleForm')
            ->assertSet('showOpenBaleModal', false)
            ->assertDispatched('toast');

        $bale->refresh();
        $this->assertEquals('opened', $bale->status);
        $this->assertEquals(2, $bale->roll_count);
        $this->assertEquals(100.00, (float)$bale->actual_recorded_length);
        $this->assertCount(2, $bale->rolls);
    }
}
