<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\Supplier;
use App\Livewire\Factory\RawMaterialPurchaseEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

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

        // Select Fabric Category -> Expect Length-Based labels
        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->assertSet('unitType', 'length_based')
            ->assertSet('unitName', 'Meters');

        // Select Subsidiary Category -> Expect Other labels
        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_category_id', $this->subsidiaryCategory->id)
            ->assertSet('unitType', 'other')
            ->assertSet('unitName', 'Pieces');
    }

    public function test_purchase_form_calculates_total_amount_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('raw_material_category_id', $this->fabricCategory->id)
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
            ->set('lot_number', 'LOT-2026-991')
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('num_bales', 1)
            ->set('bale_items', [
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #1',
                    'item_name' => 'Cotton Voile',
                    'design_number' => 'DN-001',
                    'stock_id' => 'STK-001',
                    'declared_length' => '250.00',
                    'cost_per_unit' => '100.00',
                    'photo' => null,
                ]
            ])
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        // Verify the inventory batch exists
        $batch = InventoryBatch::where('invoice_number', 'INV-9912')->first();
        $this->assertNotNull($batch);

        // Verify sequential generation for the year 2026
        $this->assertEquals('BAT-2026-0001', $batch->batch_number);
        $this->assertEquals('LOT-2026-991', $batch->lot_number);

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
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('supplier_name', '')
            ->set('purchase_date', '')
            ->set('invoice_number', '')
            ->set('lot_number', '')
            ->set('num_bales', '')
            ->set('purchase_rate', '')
            ->call('savePurchaseEntry')
            ->assertHasErrors([
                'supplier_name',
                'purchase_date',
                'invoice_number',
                'lot_number',
                'num_bales',
            ]);
    }

    public function test_purchase_form_supports_individual_bale_lengths_when_equal_length_toggled_off()
    {
        $this->actingAs($this->admin);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('supplier_name', 'TexVenture Custom Bales')
            ->set('purchase_date', '2026-05-20')
            ->set('invoice_number', 'INV-CUSTOM-01')
            ->set('lot_number', 'LOT-CUSTOM-01')
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('num_bales', 3)
            ->set('all_bales_equal_length', false)
            ->set('bale_items', [
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #1',
                    'item_name' => '',
                    'design_number' => '',
                    'stock_id' => 'STK-001',
                    'declared_length' => '300',
                    'cost_per_unit' => '100.00',
                    'photo' => null,
                ],
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #2',
                    'item_name' => '',
                    'design_number' => '',
                    'stock_id' => 'STK-002',
                    'declared_length' => '280',
                    'cost_per_unit' => '100.00',
                    'photo' => null,
                ],
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #3',
                    'item_name' => '',
                    'design_number' => '',
                    'stock_id' => 'STK-003',
                    'declared_length' => '310',
                    'cost_per_unit' => '100.00',
                    'photo' => null,
                ],
            ])
            ->assertSet('quantity_received', '890')
            ->assertSet('total_amount', 89000.00)
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        $batch = InventoryBatch::where('invoice_number', 'INV-CUSTOM-01')->first();
        $this->assertNotNull($batch);
        $this->assertEquals(890.00, (float) $batch->quantity_received);
        $this->assertCount(3, $batch->bales);

        $lengths = $batch->bales->pluck('declared_length')->map(fn($l) => (float) $l)->toArray();
        $this->assertEquals([300.0, 280.0, 310.0], $lengths);
    }

    public function test_purchase_entry_saves_lot_number_supplier_and_per_bale_details()
    {
        $this->actingAs($this->admin);

        $supplier = Supplier::create([
            'name' => 'Fabric Co.',
            'whatsapp_number' => '+91 98765 43210',
        ]);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('supplier_id', $supplier->id)
            ->set('purchase_date', '2026-08-30')
            ->set('invoice_number', 'INV-2026-991')
            ->set('lot_number', 'LOT-2026-014')
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('num_bales', 2)
            ->set('bale_items', [
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #1',
                    'item_name' => 'Cotton Voile Print',
                    'design_number' => 'DN-4021',
                    'stock_id' => 'STK-2026-01',
                    'declared_length' => '300.00',
                    'cost_per_unit' => '120.00',
                    'photo' => null,
                ],
                [
                    'raw_material_id' => $this->fabric->id,
                    'bale_number' => 'Bale #2',
                    'item_name' => 'Cotton Voile Solid',
                    'design_number' => 'DN-4022',
                    'stock_id' => 'STK-2026-02',
                    'declared_length' => '250.00',
                    'cost_per_unit' => '130.00',
                    'photo' => null,
                ]
            ])
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        $batch = InventoryBatch::where('invoice_number', 'INV-2026-991')->first();
        $this->assertNotNull($batch);
        $this->assertEquals($supplier->id, $batch->supplier_id);
        $this->assertEquals('Fabric Co.', $batch->supplier_name);
        $this->assertEquals('LOT-2026-014', $batch->lot_number);
        $this->assertEquals(550.00, (float) $batch->quantity_received);

        $bales = $batch->bales;
        $this->assertCount(2, $bales);

        $this->assertEquals('DN-4021', $bales[0]->design_number);
        $this->assertEquals('STK-2026-01', $bales[0]->stock_id);
        $this->assertEquals(300.00, (float) $bales[0]->declared_length);
        $this->assertEquals(120.00, (float) $bales[0]->cost_per_unit);
        $this->assertEquals(36000.00, (float) $bales[0]->total_cost);

        $this->assertEquals('DN-4022', $bales[1]->design_number);
        $this->assertEquals('STK-2026-02', $bales[1]->stock_id);
        $this->assertEquals(250.00, (float) $bales[1]->declared_length);
        $this->assertEquals(130.00, (float) $bales[1]->cost_per_unit);
        $this->assertEquals(32500.00, (float) $bales[1]->total_cost);
    }

    public function test_multi_material_per_bale_creates_distinct_batches()
    {
        $this->actingAs($this->admin);

        $supplier = Supplier::create([
            'name' => 'Multi Fabric Supplier',
        ]);

        $fabric1 = $this->fabric;
        $fabric2 = RawMaterial::create([
            'name' => 'Rayon Satin',
            'raw_material_category_id' => $this->fabricCategory->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('supplier_id', $supplier->id)
            ->set('purchase_date', '2026-09-01')
            ->set('invoice_number', 'INV-MULTI-100')
            ->set('lot_number', 'LOT-MULTI-100')
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('num_bales', 2)
            ->set('bale_items', [
                [
                    'raw_material_id' => $fabric1->id,
                    'bale_number' => 'Bale #1',
                    'design_number' => 'DN-F1',
                    'stock_id' => 'STK-01',
                    'declared_length' => '200.00',
                    'cost_per_unit' => '150.00',
                    'photo' => null,
                ],
                [
                    'raw_material_id' => $fabric2->id,
                    'bale_number' => 'Bale #2',
                    'design_number' => 'DN-F2',
                    'stock_id' => 'STK-02',
                    'declared_length' => '150.00',
                    'cost_per_unit' => '180.00',
                    'photo' => null,
                ],
            ])
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        $batches = InventoryBatch::where('invoice_number', 'INV-MULTI-100')->get();
        $this->assertCount(2, $batches);

        $batch1 = $batches->firstWhere('raw_material_id', $fabric1->id);
        $batch2 = $batches->firstWhere('raw_material_id', $fabric2->id);

        $this->assertNotNull($batch1);
        $this->assertNotNull($batch2);
        $this->assertEquals(200.00, (float) $batch1->quantity_received);
        $this->assertEquals(150.00, (float) $batch2->quantity_received);
    }

    public function test_multiple_items_inside_single_bale_creates_inventory_bales_properly()
    {
        $this->actingAs($this->admin);

        $fabric1 = $this->fabric;
        $fabric2 = RawMaterial::create([
            'name' => 'Silk Cotton Blend',
            'raw_material_category_id' => $this->fabricCategory->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        Livewire::test(RawMaterialPurchaseEntry::class)
            ->set('supplier_name', 'Bale Master Co.')
            ->set('purchase_date', '2026-09-09')
            ->set('invoice_number', 'INV-BALE-MULTI-1')
            ->set('lot_number', 'LOT-BALE-MULTI-1')
            ->set('raw_material_category_id', $this->fabricCategory->id)
            ->set('num_bales', 1)
            ->set('bale_items', [
                [
                    'bale_number' => 'BALE-2026-0201',
                    'items' => [
                        [
                            'raw_material_id' => $fabric1->id,
                            'design_number' => 'DN-101',
                            'stock_id' => 'STK-201-001',
                            'declared_length' => '45.00',
                            'cost_per_unit' => '100.00',
                            'photo' => null,
                        ],
                        [
                            'raw_material_id' => $fabric2->id,
                            'design_number' => 'DN-102',
                            'stock_id' => 'STK-201-002',
                            'declared_length' => '55.00',
                            'cost_per_unit' => '120.00',
                            'photo' => null,
                        ]
                    ]
                ]
            ])
            ->call('savePurchaseEntry')
            ->assertRedirect(route('factory.raw-materials.index'));

        $batches = InventoryBatch::where('invoice_number', 'INV-BALE-MULTI-1')->get();
        $this->assertCount(2, $batches);

        $batch1 = $batches->firstWhere('raw_material_id', $fabric1->id);
        $batch2 = $batches->firstWhere('raw_material_id', $fabric2->id);

        $this->assertNotNull($batch1);
        $this->assertNotNull($batch2);

        $this->assertEquals(45.00, (float) $batch1->quantity_received);
        $this->assertEquals(55.00, (float) $batch2->quantity_received);

        // Both inventory bales should share the same bale_number "BALE-2026-0201"
        $bale1 = $batch1->bales()->first();
        $bale2 = $batch2->bales()->first();

        $this->assertEquals('BALE-2026-0201', $bale1->bale_number);
        $this->assertEquals('DN-101', $bale1->design_number);
        $this->assertEquals('STK-201-001', $bale1->stock_id);

        $this->assertEquals('BALE-2026-0201', $bale2->bale_number);
        $this->assertEquals('DN-102', $bale2->design_number);
        $this->assertEquals('STK-201-002', $bale2->stock_id);
    }
}
