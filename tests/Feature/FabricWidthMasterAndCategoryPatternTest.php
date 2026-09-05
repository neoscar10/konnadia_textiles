<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FabricWidth;
use App\Models\ManufacturingProductCategory;
use App\Models\Task;
use App\Livewire\Admin\Production\FabricWidthMasterPage;
use App\Livewire\Admin\Production\ManufacturingProductCategoryPage;
use App\Livewire\Factory\AddManufacturingProductForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FabricWidthMasterAndCategoryPatternTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_fabric_width_master_crud_operations(): void
    {
        $this->actingAs($this->admin);

        // 1. Create fabric width
        Livewire::test(FabricWidthMasterPage::class)
            ->set('value', '48')
            ->set('unit', 'Inch')
            ->call('saveWidth')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fabric_widths', [
            'value' => 48.00,
            'unit'  => 'Inch',
            'name'  => '48 Inch',
        ]);

        $width = FabricWidth::where('value', 48)->first();

        // 2. Edit fabric width
        Livewire::test(FabricWidthMasterPage::class)
            ->call('editWidth', $width->id)
            ->set('value', '50')
            ->call('saveWidth')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fabric_widths', [
            'id'    => $width->id,
            'value' => 50.00,
            'name'  => '50 Inch',
        ]);

        // 3. Delete fabric width when not in use
        Livewire::test(FabricWidthMasterPage::class)
            ->call('deleteWidth', $width->id);

        $this->assertDatabaseMissing('fabric_widths', ['id' => $width->id]);
    }

    public function test_category_default_task_sequence_save(): void
    {
        $this->actingAs($this->admin);

        $task1 = Task::create(['name' => 'Cutting', 'code' => 'TSK-001', 'status' => true]);
        $task2 = Task::create(['name' => 'Stitching', 'code' => 'TSK-002', 'status' => true]);

        Livewire::test(ManufacturingProductCategoryPage::class)
            ->set('name', 'Bedsheets Special')
            ->set('defaultTasksList', [
                ['task_id' => (string) $task1->id, 'standard_labor_rate' => '15.00', 'is_final_step' => false],
                ['task_id' => (string) $task2->id, 'standard_labor_rate' => '20.00', 'is_final_step' => true],
            ])
            ->call('saveCategory')
            ->assertHasNoErrors();

        $category = ManufacturingProductCategory::where('name', 'Bedsheets Special')->first();
        $this->assertNotNull($category);

        $this->assertDatabaseHas('manufacturing_category_tasks', [
            'manufacturing_product_category_id' => $category->id,
            'task_id' => $task1->id,
            'sequence_number' => 1,
        ]);

        $this->assertDatabaseHas('manufacturing_category_tasks', [
            'manufacturing_product_category_id' => $category->id,
            'task_id' => $task2->id,
            'sequence_number' => 2,
            'is_final_step' => true,
        ]);
    }
}
