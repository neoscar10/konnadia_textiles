<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\JobWastage;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ProductionJob;
use App\Models\ProductionBatch;
use App\Models\Task;
use App\Livewire\Factory\WastageLogPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WastageLogPageTest extends TestCase
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
    public function wastage_log_page_can_be_rendered()
    {
        $response = $this->actingAs($this->admin)->get(route('factory.wastage-log.index'));
        $response->assertStatus(200);
        $response->assertSee('Wastage & Scrap Log');
    }

    /** @test */
    public function wastage_log_livewire_component_calculates_kpis_and_filters_records()
    {
        $product = ManufacturingProduct::create([
            'name' => 'Test Cotton Sheet',
            'code' => 'MP-COT-01',
            'status' => 'active',
        ]);

        $pattern = ManufacturingProductPattern::create([
            'manufacturing_product_id' => $product->id,
            'name' => 'King Size Pattern 240x260',
            'fabric_length' => 2.6,
        ]);

        $taskCutting = Task::create(['name' => 'Fabric Cutting', 'code' => 'TSK-CUT', 'status' => true]);
        $taskIroning = Task::create(['name' => 'Ironing', 'code' => 'TSK-IRN', 'status' => true]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-8888',
            'manufacturing_product_id' => $product->id,
            'planned_quantity' => 200,
            'status' => 'Completed',
        ]);

        $job = ProductionJob::create([
            'job_code' => 'JOB-2026-8888',
            'production_batch_id' => $batch->batch_code,
            'production_batch_db_id' => $batch->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'target_quantity' => 200,
            'status' => 'completed',
        ]);

        // Non-zero scrap wastage
        JobWastage::create([
            'job_code' => $job->job_code,
            'production_job_id' => $job->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'task_id' => $taskCutting->id,
            'wastage_type' => 'scrap',
            'quantity_wasted' => 10.00,
            'reason' => 'Cutting edge scrap defect',
        ]);

        // Non-zero damage wastage
        JobWastage::create([
            'job_code' => $job->job_code,
            'production_job_id' => $job->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'task_id' => $taskIroning->id,
            'wastage_type' => 'damage',
            'quantity_wasted' => 5.00,
            'reason' => 'Burn mark during final ironing',
        ]);

        // Zero wastage record (should be excluded)
        JobWastage::create([
            'job_code' => $job->job_code,
            'production_job_id' => $job->id,
            'manufacturing_product_id' => $product->id,
            'pattern_id' => $pattern->id,
            'task_id' => $taskIroning->id,
            'wastage_type' => 'scrap',
            'quantity_wasted' => 0.00,
            'reason' => 'Zero wastage placeholder',
        ]);

        Livewire::actingAs($this->admin)
            ->test(WastageLogPage::class)
            ->assertDontSee('Zero wastage placeholder')
            ->assertSee('King Size Pattern 240x260')
            ->assertSee('Scrap')
            ->assertSee('Damaged')
            ->assertViewHas('totalWastageQty', function ($val) {
                return (float)$val === 15.0;
            })
            ->assertViewHas('lossIncidentsCount', function ($val) {
                return $val === 2;
            })
            ->set('search', 'Burn mark')
            ->assertSee('Burn mark during final ironing')
            ->assertDontSee('Cutting edge scrap defect')
            ->set('search', '')
            ->set('selectedWastageType', 'scrap')
            ->assertSee('Cutting edge scrap defect')
            ->assertDontSee('Burn mark during final ironing')
            ->set('selectedWastageType', 'damaged')
            ->assertSee('Burn mark during final ironing')
            ->assertDontSee('Cutting edge scrap defect');
    }
}
