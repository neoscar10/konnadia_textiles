<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UnitGroup;
use App\Models\Unit;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;
use App\Livewire\Admin\Units\UnitIndexPage;
use App\Livewire\Factory\RawMaterialManager;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin = User::factory()->create(['email' => 'unit_admin@konnadia.com']);
        $this->admin->assignRole('super_admin');

        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
    }

    public function test_unit_conversion_service_converts_between_group_units(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        $this->assertNotNull($lengthGroup);

        // 1 Meter = 100 Centimeters
        $cmVal = UnitConversionService::convert(1.0, 'Meters', 'Centimeters', $lengthGroup->id);
        $this->assertEquals(100.0, $cmVal);

        // 100 Centimeters = 1.0 Meter
        $mVal = UnitConversionService::convert(100.0, 'Centimeters', 'Meters', $lengthGroup->id);
        $this->assertEquals(1.0, $mVal);

        // 1 Yard = 36 Inches
        $inVal = UnitConversionService::convert(1.0, 'Yards', 'Inches', $lengthGroup->id);
        $this->assertEquals(36.0, round($inVal, 2));

        // Packaging group: 2 Boxes = 200 Pieces
        $countGroup = UnitGroup::where('code', 'COUNT')->first();
        $this->assertNotNull($countGroup);
        $pcsVal = UnitConversionService::convert(2.0, 'Boxes', 'Pieces', $countGroup->id);
        $this->assertEquals(200.0, $pcsVal);
    }

    public function test_unit_management_page_can_create_group_and_add_units(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(UnitIndexPage::class)
            ->set('groupName', 'Fastener Tape Group')
            ->set('groupCode', 'TAPE')
            ->set('groupDescription', 'Zippers and fastener tapes')
            ->call('saveGroup')
            ->assertHasNoErrors();

        $group = UnitGroup::where('code', 'TAPE')->first();
        $this->assertNotNull($group);

        Livewire::test(UnitIndexPage::class)
            ->set('selectedGroupId', $group->id)
            ->set('unitName', 'Spool Tape')
            ->set('unitShortCode', 'spl')
            ->set('unitIsBase', true)
            ->set('unitRatio', 1.0)
            ->call('saveUnit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('units', [
            'unit_group_id' => $group->id,
            'name' => 'Spool Tape',
            'is_base' => true,
        ]);
    }

    public function test_raw_material_manager_loads_dynamic_units_from_category_unit_group(): void
    {
        $this->actingAs($this->admin);

        $category = RawMaterialCategory::where('code', 'CAT-FABRIC')->first() 
            ?? RawMaterialCategory::create([
                'name' => 'Fabrics',
                'code' => 'CAT-FABRIC',
                'unit_group_id' => UnitGroup::where('code', 'LENGTH')->first()->id,
                'is_active' => true,
            ]);

        $comp = Livewire::test(RawMaterialManager::class)
            ->call('openModal')
            ->set('raw_material_category_id', $category->id);

        $units = $comp->get('availableUnits');
        $this->assertContains('Meters', $units);
        $this->assertContains('Yards', $units);
        $this->assertContains('Centimeters', $units);
        $this->assertContains('Inches', $units);

        $comp->set('name', 'Premium Silk Fabric')
            ->set('unit', 'Meters')
            ->set('standard_width', 60)
            ->set('width_unit', 'Inch')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('raw_materials', [
            'name' => 'Premium Silk Fabric',
            'unit' => 'Meters',
        ]);
    }

    public function test_unit_index_page_computes_live_relationship_preview(): void
    {
        $this->actingAs($this->admin);

        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        $this->assertNotNull($lengthGroup);

        Livewire::test(UnitIndexPage::class)
            ->set('selectedGroupId', $lengthGroup->id)
            ->call('openCreateUnitModal')
            ->set('unitName', 'petermeter')
            ->set('unitShortCode', 'PM')
            ->set('unitRatio', 1000)
            ->assertSee('1 petermeter (PM) = 1,000 Meters (m)')
            ->assertSee('Every 1 PM used in manufacturing or stock counts as 1,000 m');
    }
}
