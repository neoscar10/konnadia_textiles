<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CustomizedProductionOrder;
use App\Models\RawMaterial;
use App\Models\Task;
use App\Livewire\Admin\Production\CustomizedProductionHub;
use App\Livewire\Admin\Production\CustomizedProductionDetailPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomizedProductionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    /** @test */
    public function customized_production_hub_page_can_be_rendered()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.production.customized'));
        $response->assertStatus(200);
        $response->assertSee('Customized Production Hub');
    }

    /** @test */
    public function can_create_customized_production_order_from_hub()
    {
        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
        $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();

        $category = \App\Models\RawMaterialCategory::create([
            'name' => 'Fabrics',
            'code' => 'CAT-FAB-TEST',
            'unit_group_id' => $lengthGroup->id,
            'is_active' => true,
        ]);

        $fabric = RawMaterial::create([
            'name' => 'Cotton Fabric White',
            'raw_material_category_id' => $category->id,
            'unit_group_id' => $lengthGroup->id,
            'unit' => 'Meters',
            'standard_width' => 60,
            'width_unit' => 'Inch',
            'is_active' => true,
        ]);

        Task::create(['name' => 'Cutting', 'code' => 'TSK-CUT', 'status' => true]);
        Task::create(['name' => 'Stitching', 'code' => 'TSK-STITCH', 'status' => true]);

        Livewire::actingAs($this->admin)
            ->test(CustomizedProductionHub::class)
            ->set('custom_order_id', 'CUST-PROD-2026-0001')
            ->set('item_description', 'Royal Palace Custom Velvet Bedcover')
            ->set('target_quantity', 25)
            ->set('raw_material_id', $fabric->id)
            ->set('width', '108')
            ->set('length', '120')
            ->set('length_unit', 'Inch')
            ->call('createCustomOrder')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customized_production_orders', [
            'custom_order_id' => 'CUST-PROD-2026-0001',
            'item_description' => 'Royal Palace Custom Velvet Bedcover',
            'target_quantity' => 25,
            'raw_material_id' => $fabric->id,
            'width' => 108,
            'length' => 120,
            'length_unit' => 'Inch',
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function can_render_customized_production_detail_terminal_page()
    {
        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
        $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();

        $category = \App\Models\RawMaterialCategory::create([
            'name' => 'Fabrics',
            'code' => 'CAT-FAB-TEST-2',
            'unit_group_id' => $lengthGroup->id,
            'is_active' => true,
        ]);

        $fabric = RawMaterial::create([
            'name' => 'Velvet Soft Touch',
            'raw_material_category_id' => $category->id,
            'unit_group_id' => $lengthGroup->id,
            'unit' => 'Meters',
            'standard_width' => 60,
            'width_unit' => 'Inch',
            'is_active' => true,
        ]);

        $customOrder = CustomizedProductionOrder::create([
            'custom_order_id' => 'CUST-PROD-2026-0002',
            'item_description' => 'Hotel Suite Velvet Dohar',
            'target_quantity' => 10,
            'raw_material_id' => $fabric->id,
            'width' => 90,
            'length' => 100,
            'length_unit' => 'Inch',
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.production.customized.detail', $customOrder->id));
        $response->assertStatus(200);
        $response->assertSee('CUST-PROD-2026-0002');
        $response->assertSee('Hotel Suite Velvet Dohar');
    }

    /** @test */
    public function last_dynamic_task_card_defaults_to_final_stage()
    {
        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
        $lengthGroup = \App\Models\UnitGroup::where('code', 'LENGTH')->first();

        $category = \App\Models\RawMaterialCategory::create([
            'name' => 'Fabrics',
            'code' => 'CAT-FAB-TEST-3',
            'unit_group_id' => $lengthGroup->id,
            'is_active' => true,
        ]);

        $fabric = RawMaterial::create([
            'name' => 'Linen White',
            'raw_material_category_id' => $category->id,
            'unit_group_id' => $lengthGroup->id,
            'unit' => 'Meters',
            'is_active' => true,
        ]);

        $cutting = Task::create(['name' => 'Cutting', 'code' => 'TSK-C1', 'status' => true]);
        $stitching = Task::create(['name' => 'Stitching', 'code' => 'TSK-S1', 'status' => true]);
        $finishing = Task::create(['name' => 'Finishing', 'code' => 'TSK-F1', 'status' => true]);

        $customOrder = CustomizedProductionOrder::create([
            'custom_order_id' => 'CUST-PROD-2026-0003',
            'item_description' => 'Custom Linen Dohar',
            'target_quantity' => 15,
            'raw_material_id' => $fabric->id,
            'width' => 100,
            'length' => 110,
            'length_unit' => 'Inch',
            'status' => 'in_progress',
        ]);

        Livewire::actingAs($this->admin)
            ->test(CustomizedProductionDetailPage::class, ['id' => $customOrder->id])
            ->call('addDynamicTaskStage')
            ->assertViewHas('stageExecutions', function ($stages) {
                // The last stage must be final stage
                $lastStage = $stages->last();
                return $lastStage && (bool) $lastStage->is_final_step;
            });
    }
}
