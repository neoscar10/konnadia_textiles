<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Task;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\ProductionJob;
use App\Models\ManufacturingProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskMasterConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $fabricCat;
    protected RawMaterialCategory $subCat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->fabricCat = RawMaterialCategory::create([
            'name' => 'Fabric Category',
            'code' => 'CAT-FAB',
            'status' => true,
        ]);

        $this->subCat = RawMaterialCategory::create([
            'name' => 'Subsidiary Category',
            'code' => 'CAT-SUB',
            'status' => true,
        ]);
    }

    /** @test */
    public function creating_task_auto_generates_code_and_saves_basic_info()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Ironing Press')
            ->set('status', true)
            ->set('is_labor_required', true)
            ->set('consumes_raw_material', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('factory.tasks.index'));

        $task = Task::where('name', 'Ironing Press')->first();
        $this->assertNotNull($task);
        $this->assertEquals('TSK-0001', $task->code);
        $this->assertTrue($task->is_labor_required);
        $this->assertFalse($task->consumes_raw_material);
    }

    /** @test */
    public function toggling_consumes_raw_material_validation_and_sync()
    {
        $this->actingAs($this->admin);

        // Try saving with consumes = true but empty category list -> should fail
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Packing Box')
            ->set('consumes_raw_material', true)
            ->set('selected_category_ids', [])
            ->call('save')
            ->assertHasErrors(['selected_category_ids']);

        // Save successfully with category selected
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Packing Box')
            ->set('consumes_raw_material', true)
            ->set('selected_category_ids', [(string) $this->subCat->id])
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::where('name', 'Packing Box')->first();
        $this->assertNotNull($task);
        $this->assertTrue($task->consumes_raw_material);
        $this->assertCount(1, $task->rawMaterialCategories);
        $this->assertEquals($this->subCat->id, $task->rawMaterialCategories->first()->id);
    }

    /** @test */
    public function turning_off_raw_material_consumption_clears_selections()
    {
        $this->actingAs($this->admin);

        $task = Task::create([
            'name' => 'Legacy Assembly',
            'code' => 'TSK-99',
            'consumes_raw_material' => true,
            'is_labor_required' => true,
            'status' => true,
        ]);
        $task->rawMaterialCategories()->sync([$this->subCat->id, $this->fabricCat->id]);

        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class, ['id' => $task->id])
            ->set('consumes_raw_material', false)
            ->call('save')
            ->assertHasNoErrors();

        $task->refresh();
        $this->assertFalse($task->consumes_raw_material);
        $this->assertCount(0, $task->rawMaterialCategories);
    }

    /** @test */
    public function task_list_renders_and_supports_status_toggle()
    {
        $this->actingAs($this->admin);

        $task = Task::create([
            'name' => 'Cutting Stage',
            'code' => 'TSK-CUT01',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => true,
        ]);

        Livewire::test(\App\Livewire\Factory\TaskList::class)
            ->assertSee('Cutting Stage')
            ->call('toggleStatus', $task->id);

        $task->refresh();
        $this->assertFalse($task->status);
    }

    /** @test */
    public function downstream_job_detail_respects_consumes_raw_material()
    {
        $this->actingAs($this->admin);

        // 1. Create a task that does NOT consume raw material
        $nonConsumingTask = Task::create([
            'name' => 'Packaging Step No Stock',
            'code' => 'TSK-PKG-01',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => true,
        ]);

        // 2. Create a task that DOES consume raw material of category CAT-SUB
        $consumingTask = Task::create([
            'name' => 'Assembly Step Stock',
            'code' => 'TSK-ASM-02',
            'status' => true,
            'consumes_raw_material' => true,
            'is_labor_required' => true,
        ]);
        $consumingTask->rawMaterialCategories()->sync([$this->subCat->id]);

        // 3. Create a manufacturing product category
        $mpCategory = \App\Models\ManufacturingProductCategory::create([
            'name' => 'General Category',
            'code' => 'CAT-GEN-99',
            'status' => true,
        ]);

        // 4. Create a manufacturing product
        $mProduct = ManufacturingProduct::create([
            'name' => 'Test Product',
            'code' => 'MP-TEST-01',
            'manufacturing_product_category_id' => $mpCategory->id,
            'status' => 'active',
        ]);
        $mProduct->tasks()->sync([
            $nonConsumingTask->id => ['sequence_number' => 1, 'standard_labor_rate' => 10.00],
            $consumingTask->id => ['sequence_number' => 2, 'standard_labor_rate' => 12.00],
        ]);

        // 5. Create raw materials and inventory batches for CAT-SUB and CAT-FAB
        $rawMaterialSub = RawMaterial::create([
            'name' => 'Thread Sub',
            'code' => 'RAW-TH-01',
            'raw_material_category_id' => $this->subCat->id,
            'status' => true,
            'unit' => 'Meters',
        ]);
        $batchSub = InventoryBatch::create([
            'raw_material_id' => $rawMaterialSub->id,
            'batch_number' => 'BAT-SUB-01',
            'received_quantity' => 100,
            'balance_quantity' => 100,
            'unit' => 'Meters',
            'unit_cost' => 5.00,
        ]);

        $rawMaterialFabric = RawMaterial::create([
            'name' => 'Fabric Blue',
            'code' => 'RAW-FB-01',
            'raw_material_category_id' => $this->fabricCat->id,
            'status' => true,
            'unit' => 'Meters',
        ]);
        $batchFabric = InventoryBatch::create([
            'raw_material_id' => $rawMaterialFabric->id,
            'batch_number' => 'BAT-FAB-01',
            'received_quantity' => 200,
            'balance_quantity' => 200,
            'unit' => 'Meters',
            'unit_cost' => 10.00,
        ]);

        // Create a ProductionJob
        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-99',
            'manufacturing_product_id' => $mProduct->id,
            'target_quantity' => 50,
            'status' => 'pending',
        ]);
        \App\Models\JobStageExecution::create(['production_job_id' => $job->id, 'task_id' => $nonConsumingTask->id, 'sequence_number' => 1, 'target_quantity' => 50]);
        \App\Models\JobStageExecution::create(['production_job_id' => $job->id, 'task_id' => $consumingTask->id, 'sequence_number' => 2, 'target_quantity' => 50]);

        // Test JobDetailPage with non-consuming task selected
        $component = Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $job->id])
            ->set('selectedTaskId', $nonConsumingTask->id);

        $availableBatches = $component->get('availableBatches');
        $this->assertCount(0, $availableBatches, 'Should return 0 inventory batches for a non-consuming task.');

        // Test JobDetailPage with consuming task selected
        $component2 = Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $job->id])
            ->set('selectedTaskId', $consumingTask->id);

        $availableBatches2 = $component2->get('availableBatches');
        // Should only return batchSub because only CAT-SUB is mapped to the consumingTask
        $this->assertCount(1, $availableBatches2, 'Should only return 1 category-matched inventory batch.');
        $this->assertEquals($batchSub->id, $availableBatches2->first()->id);
    }

    /** @test */
    public function downstream_job_detail_enforces_labor_requirement_rules()
    {
        $this->actingAs($this->admin);

        // 1. Create a non-labor task
        $nonLaborTask = Task::create([
            'name' => 'Automated Wash',
            'code' => 'TSK-WSH-01',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => false,
        ]);

        // 2. Create a labor task
        $laborTask = Task::create([
            'name' => 'Hand stitch detail',
            'code' => 'TSK-ST-02',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => true,
        ]);

        $mpCategory = \App\Models\ManufacturingProductCategory::create([
            'name' => 'Standard Category',
            'code' => 'CAT-STD-99',
            'status' => true,
        ]);

        $mProduct = ManufacturingProduct::create([
            'name' => 'Standard Product',
            'code' => 'MP-STD-01',
            'manufacturing_product_category_id' => $mpCategory->id,
            'status' => 'active',
        ]);
        $mProduct->tasks()->sync([
            $nonLaborTask->id => ['sequence_number' => 1, 'standard_labor_rate' => 0.00, 'is_final_step' => false],
            $laborTask->id => ['sequence_number' => 2, 'standard_labor_rate' => 15.00, 'is_final_step' => true],
        ]);

        // Create job for non-labor task
        $jobNonLabor = ProductionJob::create([
            'job_code' => 'JOB-NL-01',
            'manufacturing_product_id' => $mProduct->id,
            'target_quantity' => 100,
            'status' => 'pending',
        ]);
        \App\Models\JobStageExecution::create(['production_job_id' => $jobNonLabor->id, 'task_id' => $nonLaborTask->id, 'sequence_number' => 1, 'target_quantity' => 100]);

        // Should complete successfully without validation errors because task has is_labor_required = false
        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $jobNonLabor->id])
            ->call('completeCurrentJob')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.production.jobs.index'));

        // Create job for labor task
        $jobLabor = ProductionJob::create([
            'job_code' => 'JOB-L-02',
            'manufacturing_product_id' => $mProduct->id,
            'target_quantity' => 100,
            'status' => 'pending',
        ]);
        \App\Models\JobStageExecution::create(['production_job_id' => $jobLabor->id, 'task_id' => $laborTask->id, 'sequence_number' => 1, 'target_quantity' => 100]);

        // Attempting to complete without allocating labor should throw validation error
        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $jobLabor->id])
            ->set('selectedTaskId', $laborTask->id)
            ->call('completeCurrentJob')
            ->assertHasErrors(['jobStatus']);
    }

    /** @test */
    public function task_name_and_code_must_be_unique()
    {
        $this->actingAs($this->admin);

        Task::create([
            'name' => 'Unique Task',
            'code' => 'TSK-UNIQ-1',
            'status' => true,
            'consumes_raw_material' => false,
            'is_labor_required' => true,
        ]);

        // Attempt duplicate name
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Unique Task')
            ->set('code', 'TSK-UNIQ-2')
            ->call('save')
            ->assertHasErrors(['name']);

        // Attempt duplicate code
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Unique Name 2')
            ->set('code', 'TSK-UNIQ-1')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    /** @test */
    public function block_material_consumption_if_category_not_linked_to_task()
    {
        $this->actingAs($this->admin);

        // Task only allows CAT-SUB
        $task = Task::create([
            'name' => 'Assembly Step Only Sub',
            'code' => 'TSK-ASM-SUB',
            'status' => true,
            'consumes_raw_material' => true,
            'is_labor_required' => true,
        ]);
        $task->rawMaterialCategories()->sync([$this->subCat->id]);

        $mpCategory = \App\Models\ManufacturingProductCategory::create([
            'name' => 'General Category',
            'code' => 'CAT-GEN-55',
            'status' => true,
        ]);

        $mProduct = ManufacturingProduct::create([
            'name' => 'Standard Product',
            'code' => 'MP-STD-02',
            'manufacturing_product_category_id' => $mpCategory->id,
            'status' => 'active',
        ]);
        $mProduct->tasks()->sync([
            $task->id => ['sequence_number' => 1, 'standard_labor_rate' => 15.00, 'is_final_step' => true],
        ]);

        // Create fabric raw material (not in CAT-SUB)
        $rawMaterialFabric = RawMaterial::create([
            'name' => 'Fabric Red',
            'code' => 'RAW-FR-02',
            'raw_material_category_id' => $this->fabricCat->id,
            'status' => true,
            'unit' => 'Meters',
        ]);
        $batchFabric = InventoryBatch::create([
            'raw_material_id' => $rawMaterialFabric->id,
            'batch_number' => 'BAT-FAB-02',
            'received_quantity' => 100,
            'balance_quantity' => 100,
            'unit' => 'Meters',
            'unit_cost' => 10.00,
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-TEST-C1',
            'manufacturing_product_id' => $mProduct->id,
            'task_id' => $task->id,
            'target_quantity' => 50,
            'status' => 'pending',
        ]);

        // Attempting to consume fabric (which is not allowed for this task) must throw error
        Livewire::test(\App\Livewire\Admin\Production\JobDetailPage::class, ['id' => $job->id])
            ->set('selectedTaskId', $task->id)
            ->set('materialConsumptions', [
                ['inventory_batch_id' => $batchFabric->id, 'quantity_consumed' => 10]
            ])
            ->call('saveMaterialConsumption')
            ->assertHasErrors(['materialConsumptions.0.inventory_batch_id']);
    }

    /** @test */
    public function task_default_sequence_number_configuration_and_sorting()
    {
        $this->actingAs($this->admin);

        // Configure a task with a sequence number
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class)
            ->set('name', 'Bending Step')
            ->set('sequence_number', 25)
            ->set('status', true)
            ->set('consumes_raw_material', false)
            ->set('is_labor_required', false)
            ->call('save')
            ->assertHasNoErrors();

        $bendingTask = Task::where('name', 'Bending Step')->first();
        $this->assertNotNull($bendingTask);
        $this->assertEquals(25, $bendingTask->sequence_number);

        // Edit sequence number
        Livewire::test(\App\Livewire\Factory\TaskMasterManager::class, ['id' => $bendingTask->id])
            ->set('sequence_number', 40)
            ->call('save')
            ->assertHasNoErrors();

        $bendingTask->refresh();
        $this->assertEquals(40, $bendingTask->sequence_number);

        // Verify scopeOrdered sorts correctly (nulls last, then by sequence number, then by name)
        Task::query()->delete(); // clear for clean check

        $t3 = Task::create(['name' => 'Task C', 'code' => 'T3', 'status' => true, 'sequence_number' => 30]);
        $t1 = Task::create(['name' => 'Task A', 'code' => 'T1', 'status' => true, 'sequence_number' => 10]);
        $t4 = Task::create(['name' => 'Task D', 'code' => 'T4', 'status' => true, 'sequence_number' => null]);
        $t2 = Task::create(['name' => 'Task B', 'code' => 'T2', 'status' => true, 'sequence_number' => 20]);

        $ordered = Task::ordered()->get();
        $this->assertEquals($t1->id, $ordered->get(0)->id); // Seq 10
        $this->assertEquals($t2->id, $ordered->get(1)->id); // Seq 20
        $this->assertEquals($t3->id, $ordered->get(2)->id); // Seq 30
        $this->assertEquals($t4->id, $ordered->get(3)->id); // Seq null (last)
    }

    /** @test */
    public function task_creation_and_editing_via_in_page_modal()
    {
        $this->actingAs($this->admin);

        // Open create modal and save task
        Livewire::test(\App\Livewire\Factory\TaskList::class)
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->set('name', 'Modal Created Task')
            ->set('sequence_number', 15)
            ->set('status', true)
            ->set('is_labor_required', true)
            ->set('consumes_raw_material', false)
            ->call('saveTask')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $task = Task::where('name', 'Modal Created Task')->first();
        $this->assertNotNull($task);
        $this->assertEquals(15, $task->sequence_number);

        // Open edit modal and update task
        Livewire::test(\App\Livewire\Factory\TaskList::class)
            ->call('openEditModal', $task->id)
            ->assertSet('showModal', true)
            ->assertSet('name', 'Modal Created Task')
            ->set('name', 'Modal Updated Task')
            ->call('saveTask')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

        $task->refresh();
        $this->assertEquals('Modal Updated Task', $task->name);
    }
}
