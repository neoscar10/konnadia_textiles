<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Enums\RawMaterialUnitType;
use App\Livewire\Factory\RawMaterialPurchaseEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use Carbon\Carbon;

class RawMaterialPurchaseEntryTest extends TestCase
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

    public function test_purchase_page_renders_correctly()
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('factory.raw-materials.purchase'));
        $response->assertStatus(200);
        $response->assertSee('Raw Material Purchase Entry');
    }

    public function test_purchase_form_displays_dynamic_labels_based_on_unit_type()
    {
        $this->actingAs($this->admin);

        // Select Fabric -> Expect Length-Based labels
        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_id', $this->fabric->id)
            ->assertSet('unitType', 'length_based')
            ->assertSet('unitName', 'Meters');

        // Select Button -> Expect Other labels
        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_id', $this->button->id)
            ->assertSet('unitType', 'other')
            ->assertSet('unitName', 'Pieces');
    }

    public function test_purchase_form_calculates_total_amount_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_id', $this->fabric->id)
            ->set('num_bales', 2)
            ->set('declared_bale_length', '75.25')
            ->set('purchase_rate', '120.00')
            ->assertSet('quantity_received', '150.5')
            ->assertSet('total_amount', 18060.00);
    }

    public function test_save_purchase_entry_generates_batch_and_syncs_backward_compatibility_fields()
    {
        $this->actingAs($this->admin);

        $purchaseDate = '2026-05-20';

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('supplier_name', 'TexVenture Co.')
            ->set('purchase_date', $purchaseDate)
            ->set('invoice_number', 'INV-9912')
            ->set('raw_material_id', $this->fabric->id)
            ->set('num_bales', 1)
            ->set('declared_bale_length', '250.00')
            ->set('purchase_rate', '100.00')
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        // Verify the inventory batch exists
        $batch = InventoryBatch::where('invoice_number', 'INV-9912')->first();
        $this->assertNotNull($batch);

        // Verify sequential generation for the year 2026
        $this->assertEquals('BAT-2026-0001', $batch->batch_number);

        // Verify the primary fields
        $this->assertEquals(250.00, (float)$batch->quantity_received);
        $this->assertEquals(250.00, (float)$batch->balance_quantity);
        $this->assertEquals(100.00, (float)$batch->purchase_rate);
        $this->assertEquals(25000.00, (float)$batch->total_amount);
        $this->assertEquals('active', $batch->status);
        $this->assertEquals('Meters', $batch->unit);

        // Verify backward compatibility helper fields
        $this->assertEquals(250.00, (float)$batch->received_quantity);
        $this->assertEquals(100.00, (float)$batch->unit_cost);
        $this->assertEquals(0.00, (float)$batch->quantity_consumed);
    }

    public function test_sequential_batch_numbering_for_same_year()
    {
        $this->actingAs($this->admin);
        
        $purchaseDate = '2026-05-20';

        // Save first batch
        InventoryBatch::create([
            'raw_material_id' => $this->fabric->id,
            'supplier_name' => 'Supplier A',
            'purchase_date' => $purchaseDate,
            'invoice_number' => 'INV-001',
            'quantity_received' => 100,
            'balance_quantity' => 100,
            'purchase_rate' => 50,
            'total_amount' => 5000,
            'unit' => 'Meters',
        ]);

        // Save second batch in same year
        InventoryBatch::create([
            'raw_material_id' => $this->button->id,
            'supplier_name' => 'Supplier B',
            'purchase_date' => $purchaseDate,
            'invoice_number' => 'INV-002',
            'quantity_received' => 200,
            'balance_quantity' => 200,
            'purchase_rate' => 5,
            'total_amount' => 1000,
            'unit' => 'Pieces',
        ]);

        $batches = InventoryBatch::orderBy('id', 'asc')->get();
        $this->assertEquals('BAT-2026-0001', $batches[0]->batch_number);
        $this->assertEquals('BAT-2026-0002', $batches[1]->batch_number);
    }

    public function test_purchase_form_validation_requires_all_fields()
    {
        $this->actingAs($this->admin);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_id', $this->fabric->id)
            ->set('supplier_name', '')
            ->set('purchase_date', '')
            ->set('invoice_number', '')
            ->set('num_bales', '')
            ->set('declared_bale_length', '')
            ->set('purchase_rate', '')
            ->call('savePurchaseEntry')
            ->assertHasErrors([
                'supplier_name',
                'purchase_date',
                'invoice_number',
                'num_bales',
                'declared_bale_length',
                'purchase_rate',
            ]);
    }
}
