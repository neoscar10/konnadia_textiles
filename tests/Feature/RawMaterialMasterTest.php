<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Enums\RawMaterialUnitType;
use App\Livewire\Factory\RawMaterialList;
use App\Livewire\Factory\RawMaterialManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class RawMaterialMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);

        // Create and seed categories
        $this->seedCategories();

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super_admin');
    }

    protected function seedCategories(): void
    {
        RawMaterialCategory::updateOrCreate(['code' => 'CAT-FAB'], [
            'name' => 'Fabric',
            'code' => 'CAT-FAB',
            'unit_type' => 'length_based',
            'description' => 'Primary fabrics measured in length units.',
            'is_active' => true,
        ]);

        RawMaterialCategory::updateOrCreate(['code' => 'CAT-SUB'], [
            'name' => 'Subsidiary Materials',
            'code' => 'CAT-SUB',
            'unit_type' => 'other',
            'description' => 'Buttons, zippers, and other subsidiary materials.',
            'is_active' => true,
        ]);

        RawMaterialCategory::updateOrCreate(['code' => 'CAT-STITCH'], [
            'name' => 'Stitching Materials',
            'code' => 'CAT-STITCH',
            'unit_type' => 'other',
            'description' => 'Threads, bobbins, needles.',
            'is_active' => true,
        ]);

        RawMaterialCategory::updateOrCreate(['code' => 'CAT-PKG'], [
            'name' => 'Packaging Materials',
            'code' => 'CAT-PKG',
            'unit_type' => 'other',
            'description' => 'Poly bags, boxes, labels.',
            'is_active' => true,
        ]);
    }

    // ─── AUTO-CODE GENERATION ───────────────────────────────────────────────

    public function test_raw_material_code_is_auto_generated()
    {
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        $mat = RawMaterial::create([
            'name' => 'Cotton Poplin 60s',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'standard_width' => 58.00,
            'width_unit' => 'Inch',
        ]);

        $this->assertNotNull($mat->code);
        $this->assertStringStartsWith('RM-', $mat->code);

        // Second material gets incremented code
        $mat2 = RawMaterial::create([
            'name' => 'Linen Blend',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Yards',
            'standard_width' => 44.00,
            'width_unit' => 'Inch',
        ]);

        $this->assertNotEquals($mat->code, $mat2->code);
        $this->assertStringStartsWith('RM-', $mat2->code);
    }

    // ─── CATEGORY UNIT TYPE ENFORCEMENT ────────────────────────────────────

    public function test_raw_material_category_has_correct_unit_types()
    {
        $fabric = RawMaterialCategory::where('code', 'CAT-FAB')->first();
        $subsidiary = RawMaterialCategory::where('code', 'CAT-SUB')->first();

        $this->assertEquals(RawMaterialUnitType::LENGTH_BASED, $fabric->unit_type);
        $this->assertEquals(RawMaterialUnitType::OTHER, $subsidiary->unit_type);

        // Valid units for fabric
        $this->assertContains('Meters', $fabric->valid_units);
        $this->assertContains('Yards', $fabric->valid_units);
        $this->assertNotContains('Pieces', $fabric->valid_units);

        // Valid units for subsidiary
        $this->assertContains('Pieces', $subsidiary->valid_units);
        $this->assertContains('Rolls', $subsidiary->valid_units);
        $this->assertNotContains('Meters', $subsidiary->valid_units);
    }

    public function test_raw_material_can_be_created_with_valid_units()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $cat->id)
            ->set('name', 'Pure Silk Charmeuse')
            ->set('unit', 'Meters')
            ->set('standard_width', 54.00)
            ->set('width_unit', 'Inch')
            ->set('is_active', true)
            ->call('save')
            ->assertDispatched('raw-material-saved');

        $this->assertDatabaseHas('raw_materials', [
            'name' => 'Pure Silk Charmeuse',
            'unit' => 'Meters',
            'standard_width' => 54.00,
            'width_unit' => 'Inch',
            'raw_material_category_id' => $cat->id,
        ]);
    }

    public function test_raw_material_validation_fails_on_invalid_unit_for_category()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        // Try using "Pieces" for a fabric category (should fail)
        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $cat->id)
            ->set('name', 'Invalid Unit Fabric')
            ->set('unit', 'Pieces')
            ->set('standard_width', 58.00)
            ->set('width_unit', 'Inch')
            ->set('is_active', true)
            ->call('save')
            ->assertHasErrors(['unit']);
    }

    // ─── WIDTH VALIDATION TESTS ──────────────────────────────────────────

    public function test_raw_material_width_fields_required_for_length_based()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        // Fails when standard_width is missing
        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $cat->id)
            ->set('name', 'Missing Width Fabric')
            ->set('unit', 'Meters')
            ->set('width_unit', 'Inch')
            ->call('save')
            ->assertHasErrors(['standard_width']);

        // Fails when width_unit is invalid/missing
        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $cat->id)
            ->set('name', 'Missing Width Unit Fabric')
            ->set('unit', 'Meters')
            ->set('standard_width', 58.00)
            ->set('width_unit', '')
            ->call('save')
            ->assertHasErrors(['width_unit']);
    }

    public function test_raw_material_width_fields_not_required_for_other_categories()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-SUB')->first();

        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $cat->id)
            ->set('name', 'Zipper YKK')
            ->set('unit', 'Pieces')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('raw-material-saved');

        $this->assertDatabaseHas('raw_materials', [
            'name' => 'Zipper YKK',
            'standard_width' => null,
            'width_unit' => null,
        ]);
    }

    // ─── UNIT CONVERSIONS ────────────────────────────────────────────────

    public function test_raw_material_length_conversions()
    {
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        $mat = RawMaterial::create([
            'name' => 'Cotton Voile',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Yards',
        ]);

        // 10 Yards to Meters should convert using factor (1 Yard = 0.9144 Meters)
        // 10 * 0.9144 = 9.144 Meters
        $convertedToMeters = $mat->convertQuantity(10.0, 'Meters');
        $this->assertEquals(9.144, $convertedToMeters);

        // 10 Yards to Inches should convert correctly
        // 10 * 36 = 360 Inches
        $convertedToInches = $mat->convertQuantity(10.0, 'Inches');
        $this->assertEquals(360.0, $convertedToInches);

        // Converting to the same unit yields same quantity
        $this->assertEquals(10.0, $mat->convertQuantity(10.0, 'Yards'));
    }

    // ─── STATUS TOGGLE ────────────────────────────────────────────────────

    public function test_raw_material_status_can_be_toggled()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-SUB')->first();

        $material = RawMaterial::create([
            'name' => 'YKK Zip #5',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Pieces',
            'is_active' => true,
        ]);

        $this->assertTrue($material->is_active);

        Livewire::test(RawMaterialList::class)
            ->call('toggleStatus', $material->id);

        $material->refresh();
        $this->assertFalse($material->is_active);

        // Toggle back
        Livewire::test(RawMaterialList::class)
            ->call('toggleStatus', $material->id);

        $material->refresh();
        $this->assertTrue($material->is_active);
    }

    // ─── LIST & SEARCH ────────────────────────────────────────────────────

    public function test_raw_material_list_renders_and_supports_search()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        RawMaterial::create([
            'name' => 'Muslin Cotton',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'standard_width' => 44.00,
            'width_unit' => 'Inch',
        ]);

        RawMaterial::create([
            'name' => 'Polyester Blend',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Yards',
            'standard_width' => 58.00,
            'width_unit' => 'Inch',
        ]);

        Livewire::test(RawMaterialList::class)
            ->assertSee('Muslin Cotton')
            ->assertSee('Polyester Blend')
            ->set('search', 'Muslin')
            ->assertSee('Muslin Cotton')
            ->assertDontSee('Polyester Blend');
    }

    // ─── CATEGORY FILTER ──────────────────────────────────────────────────

    public function test_raw_material_list_filters_by_category()
    {
        $this->actingAs($this->admin);
        $fabricCat = RawMaterialCategory::where('code', 'CAT-FAB')->first();
        $subCat = RawMaterialCategory::where('code', 'CAT-SUB')->first();

        RawMaterial::create([
            'name' => 'Silk Fabric A',
            'raw_material_category_id' => $fabricCat->id,
            'unit' => 'Meters',
            'standard_width' => 44.00,
            'width_unit' => 'Inch',
        ]);

        RawMaterial::create([
            'name' => 'Button Large',
            'raw_material_category_id' => $subCat->id,
            'unit' => 'Pieces',
        ]);

        Livewire::test(RawMaterialList::class)
            ->assertSee('Silk Fabric A')
            ->assertSee('Button Large')
            ->set('categoryFilter', $fabricCat->id)
            ->assertSee('Silk Fabric A')
            ->assertDontSee('Button Large');
    }

    // ─── EDIT EXISTING MATERIAL ───────────────────────────────────────────

    public function test_raw_material_can_be_edited()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        $material = RawMaterial::create([
            'name' => 'Raw Cotton Voile',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'standard_width' => 44.00,
            'width_unit' => 'Inch',
        ]);

        Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal', materialId: $material->id)
            ->assertSet('name', 'Raw Cotton Voile')
            ->assertSet('unit', 'Meters')
            ->assertSet('standard_width', 44.00)
            ->assertSet('width_unit', 'Inch')
            ->set('name', 'Premium Cotton Voile')
            ->set('unit', 'Yards')
            ->set('standard_width', 58.00)
            ->set('width_unit', 'CM')
            ->call('save')
            ->assertDispatched('raw-material-saved');

        $material->refresh();
        $this->assertEquals('Premium Cotton Voile', $material->name);
        $this->assertEquals('Yards', $material->unit);
        $this->assertEquals(58.00, $material->standard_width);
        $this->assertEquals('CM', $material->width_unit);
    }

    // ─── DELETE PROTECTION ────────────────────────────────────────────────

    public function test_raw_material_with_batches_cannot_be_deleted()
    {
        $this->actingAs($this->admin);
        $cat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        $material = RawMaterial::create([
            'name' => 'Protected Fabric',
            'raw_material_category_id' => $cat->id,
            'unit' => 'Meters',
            'standard_width' => 44.00,
            'width_unit' => 'Inch',
        ]);

        // Create a linked inventory batch
        \App\Models\InventoryBatch::create([
            'raw_material_id' => $material->id,
            'batch_number' => 'BATCH-TEST-001',
            'received_quantity' => 100,
            'balance_quantity' => 100,
            'unit' => 'Meters',
            'unit_cost' => 50.00,
        ]);

        Livewire::test(RawMaterialList::class)
            ->call('delete', $material->id)
            ->assertDispatched('toast', function ($name, $params) {
                return str_contains($params['message'], 'Cannot delete');
            });

        // Material should still exist
        $this->assertDatabaseHas('raw_materials', ['id' => $material->id]);
    }

    // ─── CATEGORY SEEDER IDEMPOTENCY ─────────────────────────────────────

    public function test_seeder_creates_all_four_standard_categories()
    {
        // Categories were already seeded in setUp()
        $this->assertDatabaseHas('raw_material_categories', ['code' => 'CAT-FAB', 'unit_type' => 'length_based']);
        $this->assertDatabaseHas('raw_material_categories', ['code' => 'CAT-SUB', 'unit_type' => 'other']);
        $this->assertDatabaseHas('raw_material_categories', ['code' => 'CAT-STITCH', 'unit_type' => 'other']);
        $this->assertDatabaseHas('raw_material_categories', ['code' => 'CAT-PKG', 'unit_type' => 'other']);

        // Run again — should be idempotent (no duplicates)
        $this->seedCategories();
        $this->assertEquals(4, RawMaterialCategory::count());
    }

    // ─── DYNAMIC UNIT SELECTOR ────────────────────────────────────────────

    public function test_changing_category_updates_available_units()
    {
        $this->actingAs($this->admin);
        $fabricCat = RawMaterialCategory::where('code', 'CAT-FAB')->first();
        $subCat = RawMaterialCategory::where('code', 'CAT-SUB')->first();

        $component = Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal');

        // Select fabric category → length units
        $component->set('raw_material_category_id', $fabricCat->id)
            ->assertSet('availableUnits', $fabricCat->valid_units);

        // Switch to subsidiary → other units
        $component->set('raw_material_category_id', $subCat->id)
            ->assertSet('availableUnits', $subCat->valid_units);
    }

    public function test_selecting_material_unit_syncs_width_unit_with_manual_override_option()
    {
        $this->actingAs($this->admin);
        $fabricCat = RawMaterialCategory::where('code', 'CAT-FAB')->first();

        $component = Livewire::test(RawMaterialManager::class)
            ->dispatch('open-raw-material-modal')
            ->set('raw_material_category_id', $fabricCat->id)
            ->call('selectUnit', 'Meters')
            ->assertSet('unit', 'Meters')
            ->assertSet('width_unit', 'Meters');

        // Manual override for width_unit
        $component->set('width_unit', 'Inch')
            ->assertSet('unit', 'Meters')
            ->assertSet('width_unit', 'Inch');
    }
}
