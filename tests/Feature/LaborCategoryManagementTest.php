<?php

namespace Tests\Feature;

use App\Livewire\Admin\Labor\LaborCategoryList;
use App\Livewire\Admin\Labor\LaborList;
use App\Livewire\Factory\TaskList;
use App\Models\Labor;
use App\Models\LaborCategory;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaborCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'superadmin@konnadia.com',
        ]);
    }

    /** @test */
    public function it_can_create_a_labor_category()
    {
        $this->actingAs($this->admin);

        Livewire::test(LaborCategoryList::class)
            ->set('name', 'Tailor')
            ->set('description', 'Stitching and garment tailors')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('labor_categories', [
            'name' => 'Tailor',
            'code' => 'LCAT-0001',
            'description' => 'Stitching and garment tailors',
            'status' => true,
        ]);
    }

    /** @test */
    public function it_can_link_task_to_labor_category()
    {
        $this->actingAs($this->admin);

        $category = LaborCategory::create([
            'name' => 'Tailor Category',
            'code' => 'LCAT-0001',
        ]);

        Livewire::test(TaskList::class)
            ->set('name', 'Stitching Task')
            ->set('is_labor_required', true)
            ->set('labor_category_id', $category->id)
            ->call('saveTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'name' => 'Stitching Task',
            'labor_category_id' => $category->id,
            'is_labor_required' => true,
        ]);
    }

    /** @test */
    public function it_preselects_category_tasks_when_creating_labor()
    {
        $this->actingAs($this->admin);

        $category = LaborCategory::create([
            'name' => 'Cutting Category',
            'code' => 'LCAT-0001',
        ]);

        $task1 = Task::create([
            'name' => 'Cutting Step 1',
            'code' => 'TSK-001',
            'labor_category_id' => $category->id,
            'is_labor_required' => true,
        ]);

        $task2 = Task::create([
            'name' => 'Cutting Step 2',
            'code' => 'TSK-002',
            'labor_category_id' => $category->id,
            'is_labor_required' => true,
        ]);

        Livewire::test(LaborList::class)
            ->set('name', 'John Cutter')
            ->set('monthly_salary', 1000)
            ->set('labor_category_id', $category->id)
            ->assertSet('authorized_tasks', [(string)$task1->id, (string)$task2->id])
            ->call('save')
            ->assertHasNoErrors();

        $labor = Labor::where('name', 'John Cutter')->first();
        $this->assertNotNull($labor);
        $this->assertEquals($category->id, $labor->labor_category_id);
        $this->assertTrue($labor->tasks->contains($task1->id));
        $this->assertTrue($labor->tasks->contains($task2->id));
    }

    /** @test */
    public function task_get_eligible_labors_filters_by_category()
    {
        $tailorCat = LaborCategory::create(['name' => 'Tailor Category', 'code' => 'LCAT-0001']);
        $laundryCat = LaborCategory::create(['name' => 'Laundry Category', 'code' => 'LCAT-0002']);

        $task = Task::create([
            'name' => 'Stitching Job',
            'code' => 'TSK-010',
            'labor_category_id' => $tailorCat->id,
            'is_labor_required' => true,
        ]);

        $tailorLabor = Labor::create([
            'name' => 'Tailor Worker',
            'code' => 'LBR-001',
            'labor_category_id' => $tailorCat->id,
            'status' => true,
        ]);
        $tailorLabor->tasks()->attach($task->id);

        $laundryLabor = Labor::create([
            'name' => 'Laundry Worker',
            'code' => 'LBR-002',
            'labor_category_id' => $laundryCat->id,
            'status' => true,
        ]);

        $eligible = $task->getEligibleLabors();

        $this->assertTrue($eligible->contains($tailorLabor->id));
        $this->assertFalse($eligible->contains($laundryLabor->id));
    }
}
