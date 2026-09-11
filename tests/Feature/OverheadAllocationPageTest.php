<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Labor;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\MonthlyOverheadAllocation;
use App\Livewire\Factory\OverheadAllocationPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OverheadAllocationPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_overhead@konnadia.com',
        ]);
        $this->admin->assignRole('super_admin');
    }

    /** @test */
    public function overhead_allocation_page_can_be_rendered()
    {
        $response = $this->actingAs($this->admin)->get(route('factory.overhead-allocation.index'));
        $response->assertStatus(200);
        $response->assertSee('Overhead Allocation Module');
    }

    /** @test */
    public function overhead_allocation_livewire_component_calculates_and_saves()
    {
        // Create subsidiary raw material
        $cat = RawMaterialCategory::create([
            'name' => 'Stitching Materials',
            'code' => 'CAT-STITCH',
            'unit_type' => 'other',
            'is_active' => true,
        ]);

        $material = RawMaterial::create([
            'raw_material_category_id' => $cat->id,
            'name' => 'White Thread (Cones)',
            'code' => 'RM-STITCH-001',
            'unit' => 'Cone',
            'unit_cost' => 100.00,
            'opening_stock_quantity' => 50,
            'stock_balance' => 50,
            'is_active' => true,
        ]);

        // Create salaried staff
        Labor::create([
            'name' => 'Ramesh Kumar',
            'mobile_number' => '9876543210',
            'payment_method' => 'salary',
            'monthly_salary' => 15000.00,
            'status' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(OverheadAllocationPage::class)
            ->set('productionValue', 500000.00)
            ->call('addOtherOverheadLine')
            ->set('otherOverheadRows.0.category', 'Factory Rent Share')
            ->set('otherOverheadRows.0.amount', 2500.00)
            ->call('saveMonth');

        $this->assertDatabaseHas('monthly_overhead_allocations', [
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'production_value' => 500000.00,
            'salaried_staff_total' => 15000.00,
            'status' => 'saved',
        ]);

        $allocation = MonthlyOverheadAllocation::first();
        $this->assertNotNull($allocation);
        $this->assertGreaterThan(0, $allocation->total_overhead);
        $this->assertGreaterThan(0, $allocation->overhead_percentage);
    }

    /** @test */
    public function it_supports_custom_other_overhead_category()
    {
        Livewire::actingAs($this->admin)
            ->test(OverheadAllocationPage::class)
            ->set('productionValue', 100000.00)
            ->call('addOtherOverheadLine')
            ->set('otherOverheadRows.0.category', 'Other')
            ->set('otherOverheadRows.0.custom_category', 'Generator Fuel Expense')
            ->set('otherOverheadRows.0.amount', 4500.00)
            ->call('saveMonth');

        $this->assertDatabaseHas('monthly_overhead_other_items', [
            'category_name' => 'Generator Fuel Expense',
            'amount' => 4500.00,
        ]);
    }

    /** @test */
    public function it_excludes_subsidiary_materials_and_uses_quantity_based_stock_accounting()
    {
        $subCat = RawMaterialCategory::create([
            'name' => 'Subsidiary Materials',
            'code' => 'CAT-SUB',
            'unit_type' => 'other',
            'is_active' => true,
        ]);

        $stitchCat = RawMaterialCategory::create([
            'name' => 'Stitching Materials',
            'code' => 'CAT-STITCH',
            'unit_type' => 'other',
            'is_active' => true,
        ]);

        $ohdCat = RawMaterialCategory::create([
            'name' => 'General Overheads / Consumables',
            'code' => 'CAT-OHD',
            'unit_type' => 'other',
            'is_active' => true,
        ]);

        // Subsidiary item (should be excluded)
        RawMaterial::create([
            'raw_material_category_id' => $subCat->id,
            'name' => 'Metal Button 12mm',
            'code' => 'RM-SUB-001',
            'unit' => 'Pieces',
            'unit_cost' => 2.00,
            'is_active' => true,
        ]);

        // Machine Oil in Liters (should be included)
        $oil = RawMaterial::create([
            'raw_material_category_id' => $ohdCat->id,
            'name' => 'Machine Oil Grade A',
            'code' => 'RM-OHD-001',
            'unit' => 'Liters',
            'opening_stock_quantity' => 0.00,
            'is_active' => true,
        ]);

        \App\Models\InventoryBatch::create([
            'raw_material_id' => $oil->id,
            'supplier_name' => 'Oil Supplier',
            'purchase_date' => now()->format('Y-m-d'),
            'invoice_number' => 'INV-OIL-01',
            'quantity_received' => 3.00,
            'balance_quantity' => 3.00,
            'purchase_rate' => 150.00,
            'total_amount' => 450.00,
            'unit' => 'Liters',
            'created_at' => now()->startOfMonth(),
        ]);

        $test = Livewire::actingAs($this->admin)
            ->test(OverheadAllocationPage::class);

        $rows = $test->get('materialRows');

        // Verify subsidiary material is excluded
        $names = collect($rows)->pluck('name')->toArray();
        $this->assertNotContains('Metal Button 12mm', $names);
        $this->assertContains('Machine Oil Grade A', $names);

        // Find Machine Oil row
        $oilIndex = collect($rows)->search(fn($r) => $r['name'] === 'Machine Oil Grade A');
        $this->assertIsNotBool($oilIndex);

        // Set closing stock to 1 Liter
        $test->set("materialRows.{$oilIndex}.closing_stock_qty", 1.00);

        // Verify consumed qty is 2 Liters and consumed cost is 300 (2 * 150)
        $updatedRows = $test->get('materialRows');
        $this->assertEquals(2.00, (float) $updatedRows[$oilIndex]['consumed_qty']);
        $this->assertEquals(300.00, (float) $updatedRows[$oilIndex]['consumed_cost']);

        $test->call('saveMonth');

        $this->assertDatabaseHas('monthly_overhead_material_items', [
            'raw_material_id' => $oil->id,
            'opening_stock_qty' => 0.00,
            'purchases_qty' => 3.00,
            'closing_stock_qty' => 1.00,
            'consumed_qty' => 2.00,
            'consumed_cost' => 300.00,
        ]);
    }
}

