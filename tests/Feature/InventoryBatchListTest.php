<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Livewire\Factory\InventoryBatchList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class InventoryBatchListTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $fabricCategory;
    protected RawMaterialCategory $subsidiaryCategory;
    protected RawMaterial $fabric;
    protected RawMaterial $button;

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

        $this->subsidiaryCategory = RawMaterialCategory::create([
            'name' => 'Subsidiary',
            'code' => 'CAT-SUB',
            'unit_type' => 'other',
            'description' => 'Count-based subsidiary items',
            'is_active' => true,
        ]);

        $this->fabric = RawMaterial::create([
            'name' => 'Cotton Voile',
            'raw_material_category_id' => $this->fabricCategory->id,
            'unit' => 'Meters',
            'standard_width' => 58.00,
            'width_unit' => 'Inch',
            'is_active' => true,
        ]);

        $this->button = RawMaterial::create([
            'name' => 'Metal Button 12mm',
            'raw_material_category_id' => $this->subsidiaryCategory->id,
            'unit' => 'Pieces',
            'is_active' => true,
        ]);
    }

    public function test_inventory_batches_page_renders_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('factory.raw-materials.batches'));
        $response->assertStatus(200);
        $response->assertSee('Inventory Batches');
    }

    public function test_inventory_batch_listing_displays_and_filters_correctly()
    {
        $this->actingAs($this->admin);

        $batch1 = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Alpha Suppliers',
            'purchase_date' => '2026-05-20',
            'invoice_number' => 'INV-2026-101',
            'quantity_received' => 500,
            'balance_quantity' => 500,
            'purchase_rate' => 120,
            'total_amount' => 60000,
            'unit' => 'Meters',
        ]);

        $batch2 = InventoryBatch::create([
            'raw_material_id' => $this->button->id,
            'supplier_name' => 'Beta Zips Co.',
            'purchase_date' => '2026-05-21',
            'invoice_number' => 'INV-2026-102',
            'quantity_received' => 1000,
            'balance_quantity' => 0,
            'purchase_rate' => 5,
            'total_amount' => 5000,
            'unit' => 'Pieces',
            'status' => 'depleted',
        ]);

        Livewire::test(InventoryBatchList::class)
            ->assertSee('Alpha Suppliers')
            ->assertSee('Beta Zips Co.')
            // Search filter
            ->set('search', 'Alpha')
            ->assertSee('Alpha Suppliers')
            ->assertDontSee('Beta Zips Co.')
            ->set('search', '')
            // Material filter
            ->set('materialFilter', $this->button->id)
            ->assertSee('Beta Zips Co.')
            ->assertDontSee('Alpha Suppliers')
            ->set('materialFilter', '')
            // Category filter
            ->set('categoryFilter', $this->fabricCategory->id)
            ->assertSee('Alpha Suppliers')
            ->assertDontSee('Beta Zips Co.')
            ->set('categoryFilter', '')
            // Status filter
            ->set('statusFilter', 'depleted')
            ->assertSee('Beta Zips Co.')
            ->assertDontSee('Alpha Suppliers');
    }

    public function test_inventory_batch_deduct_and_restore_quantity()
    {
        $batch = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Alpha Suppliers',
            'purchase_date' => '2026-05-20',
            'invoice_number' => 'INV-2026-101',
            'quantity_received' => 500,
            'balance_quantity' => 500,
            'purchase_rate' => 120,
            'total_amount' => 60000,
            'unit' => 'Meters',
        ]);

        // Deduct 200 Meters
        $batch->deductQuantity(200.00);
        $batch->refresh();
        
        $this->assertEquals(200.00, (float)$batch->quantity_consumed);
        $this->assertEquals(300.00, (float)$batch->balance_quantity);
        $this->assertEquals('active', $batch->status);

        // Deduct 300 more Meters (total 500) -> Depletes batch
        $batch->deductQuantity(300.00);
        $batch->refresh();

        $this->assertEquals(500.00, (float)$batch->quantity_consumed);
        $this->assertEquals(0.00, (float)$batch->balance_quantity);
        $this->assertEquals('depleted', $batch->status);

        // Restore 150 Meters
        $batch->restoreQuantity(150.00);
        $batch->refresh();

        $this->assertEquals(350.00, (float)$batch->quantity_consumed);
        $this->assertEquals(150.00, (float)$batch->balance_quantity);
        $this->assertEquals('active', $batch->status);
    }

    public function test_active_scope_filters_active_batches_only()
    {
        $batchActive = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Alpha Suppliers',
            'purchase_date' => '2026-05-20',
            'invoice_number' => 'INV-1',
            'quantity_received' => 500,
            'balance_quantity' => 500,
            'purchase_rate' => 120,
            'total_amount' => 60000,
            'unit' => 'Meters',
        ]);

        $batchDepleted = InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Alpha Suppliers',
            'purchase_date' => '2026-05-20',
            'invoice_number' => 'INV-2',
            'quantity_received' => 500,
            'balance_quantity' => 0,
            'purchase_rate' => 120,
            'total_amount' => 60000,
            'unit' => 'Meters',
            'status' => 'depleted',
        ]);

        $activeBatches = InventoryBatch::active()->get();
        $this->assertTrue($activeBatches->contains('id', $batchActive->id));
        $this->assertFalse($activeBatches->contains('id', $batchDepleted->id));
    }
}
