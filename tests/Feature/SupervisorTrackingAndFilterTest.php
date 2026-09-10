<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Livewire\Admin\Production\JobIndexPage;
use App\Livewire\Admin\Production\BatchJobsDetailPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupervisorTrackingAndFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FactorySupervisor $supervisorA;
    protected FactorySupervisor $supervisorB;
    protected ManufacturingProduct $mProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $mpCat = ManufacturingProductCategory::create(['name' => 'Bedding']);
        $this->mProduct = ManufacturingProduct::create([
            'name' => 'Super King Sheet',
            'manufacturing_product_category_id' => $mpCat->id,
            'status' => 'active',
        ]);

        $this->supervisorA = FactorySupervisor::create([
            'name' => 'Alice Supervisor',
            'code' => 'SUP-ALICE',
            'status' => 'active',
        ]);

        $this->supervisorB = FactorySupervisor::create([
            'name' => 'Bob Supervisor',
            'code' => 'SUP-BOB',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_tracks_factory_supervisor_on_batch_and_job_creation()
    {
        $this->actingAs($this->admin);

        Livewire::test(JobIndexPage::class)
            ->set('factory_supervisor_id', $this->supervisorA->id)
            ->set('priority', 'Normal')
            ->set('notes', 'Test batch with Alice')
            ->set('batchProducts', [
                [
                    'manufacturing_product_id' => $this->mProduct->id,
                    'pattern_id' => null,
                    'planned_quantity' => 100,
                ],
            ])
            ->call('saveJob');

        $batch = ProductionBatch::latest()->first();
        $this->assertNotNull($batch);
        $this->assertEquals($this->supervisorA->id, $batch->factory_supervisor_id);

        $job = ProductionJob::latest()->first();
        $this->assertNotNull($job);
        $this->assertEquals($this->supervisorA->id, $job->factory_supervisor_id);
        $this->assertEquals('Alice Supervisor', $job->effective_supervisor->name);
    }

    /** @test */
    public function it_filters_batches_by_supervisor_on_job_index_page()
    {
        $this->actingAs($this->admin);

        // Initiate batch for Alice
        $workflow = resolve(\App\Services\Manufacturing\ProductionWorkflowService::class);
        $workflow->initiateBatch($this->mProduct->id, $this->supervisorA->id, 50, 'Normal', 'Batch A');

        // Initiate batch for Bob
        $workflow->initiateBatch($this->mProduct->id, $this->supervisorB->id, 80, 'Normal', 'Batch B');

        $batchA = ProductionBatch::where('factory_supervisor_id', $this->supervisorA->id)->first();
        $batchB = ProductionBatch::where('factory_supervisor_id', $this->supervisorB->id)->first();

        // Render index page without supervisor filter -> sees both
        Livewire::test(JobIndexPage::class)
            ->assertSee($batchA->batch_code)
            ->assertSee($batchB->batch_code)
            ->assertSee('Alice Supervisor')
            ->assertSee('Bob Supervisor');

        // Filter by Alice Supervisor -> sees Batch A only
        Livewire::test(JobIndexPage::class)
            ->set('supervisorFilter', (string) $this->supervisorA->id)
            ->assertSee($batchA->batch_code)
            ->assertDontSee($batchB->batch_code);

        // Filter by Bob Supervisor -> sees Batch B only
        Livewire::test(JobIndexPage::class)
            ->set('supervisorFilter', (string) $this->supervisorB->id)
            ->assertSee($batchB->batch_code)
            ->assertDontSee($batchA->batch_code);
    }

    /** @test */
    public function it_displays_supervisor_name_on_batch_jobs_detail_page()
    {
        $this->actingAs($this->admin);

        $workflow = resolve(\App\Services\Manufacturing\ProductionWorkflowService::class);
        $res = $workflow->initiateBatch($this->mProduct->id, $this->supervisorB->id, 60, 'Normal', 'Batch Detail Test');
        $batchCode = $res->getData(true)['data']['batch']['batch_code'];

        Livewire::test(BatchJobsDetailPage::class, ['batchCode' => $batchCode])
            ->assertSee('Bob Supervisor');
    }
}
