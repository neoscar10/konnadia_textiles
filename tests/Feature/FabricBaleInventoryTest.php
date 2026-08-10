<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBale;
use App\Models\InventoryBaleRoll;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Enums\RawMaterialUnitType;
use Spatie\Permission\Models\Role;

class FabricBaleInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('admin');

        $unitGroup = UnitGroup::create([
            'name' => 'Length Group',
            'code' => 'UG-LEN',
            'is_active' => true,
        ]);
        $unit = Unit::create([
            'unit_group_id' => $unitGroup->id,
            'name' => 'Meters',
            'code' => 'MTR',
            'short_code' => 'm',
            'operator' => 'multiply',
            'conversion_factor' => 1.0000,
            'is_base' => true,
            'is_active' => true,
        ]);
        $category = RawMaterialCategory::create([
            'name' => 'Fabric',
            'code' => 'CAT-FAB',
            'unit_type' => 'length_based',
            'is_active' => true,
        ]);
        RawMaterial::create([
            'name' => 'Cotton Canvas 300GSM',
            'code' => 'RM-COT-300',
            'raw_material_category_id' => $category->id,
            'unit_group_id' => $unitGroup->id,
            'unit_id' => $unit->id,
            'unit' => 'Meters',
            'standard_width' => 1.5,
            'width_unit' => 'Meters',
            'is_active' => true,
        ]);
    }

    public function test_fabric_purchase_creates_unopened_bales()
    {
        $material = RawMaterial::first();

        $batch = InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Global Fabrics Ltd',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-FAB-1001',
            'quantity_received' => 1000.0000,
            'balance_quantity' => 1000.0000,
            'purchase_rate' => 150.00,
            'total_amount' => 150000.00,
            'num_bales' => 2,
            'declared_bale_length' => 500.0000,
            'status' => 'active',
        ]);

        $batch->createBales(2, 500.00);

        $this->assertCount(2, $batch->bales);
        $this->assertEquals('unopened', $batch->bales->first()->status);
        $this->assertEquals(500.00, (float) $batch->bales->first()->declared_length);
    }

    public function test_opening_unopened_bale_creates_rolls_and_handles_mismatch()
    {
        $material = RawMaterial::first();

        $batch = InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Global Fabrics Ltd',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-FAB-1002',
            'quantity_received' => 500.0000,
            'balance_quantity' => 500.0000,
            'num_bales' => 1,
            'declared_bale_length' => 500.0000,
            'status' => 'active',
        ]);

        $bales = $batch->createBales(1, 500.00);
        $bale = $bales[0];

        // Open bale with 5 rolls summing to 498m (2m mismatch)
        $rollLengths = [100.0, 98.0, 100.0, 100.0, 100.0];
        $result = $bale->openBale($rollLengths);

        $bale->refresh();
        $this->assertEquals('opened', $bale->status);
        $this->assertTrue($result['has_mismatch']);
        $this->assertEquals(-2.0, $result['difference']);
        $this->assertCount(5, $bale->rolls);
        $this->assertEquals(498.0, (float) $bale->current_balance_length);
    }

    public function test_deducting_roll_length_updates_bale_and_batch_balances()
    {
        $material = RawMaterial::first();

        $batch = InventoryBatch::create([
            'raw_material_id' => $material->id,
            'supplier_name' => 'Global Fabrics Ltd',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-FAB-1003',
            'quantity_received' => 500.0000,
            'balance_quantity' => 500.0000,
            'status' => 'active',
        ]);

        $bales = $batch->createBales(1, 500.00);
        $bale = $bales[0];
        $bale->openBale([100.0, 100.0]);

        $roll = $bale->rolls->first();
        $roll->deductLength(50.0);

        $roll->refresh();
        $bale->refresh();

        $this->assertEquals(50.0, (float) $roll->current_balance_length);
        $this->assertEquals(150.0, (float) $bale->current_balance_length);
    }
}
