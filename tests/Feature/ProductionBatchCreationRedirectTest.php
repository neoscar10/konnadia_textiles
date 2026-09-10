<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Livewire\Admin\Production\JobIndexPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionBatchCreationRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FactorySupervisor $supervisor;
    protected ManufacturingProduct $mProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $mpCat = ManufacturingProductCategory::create(['name' => 'Bedding']);
        $this->mProduct = ManufacturingProduct::create([
            'name' => 'King Bed Sheet',
            'manufacturing_product_category_id' => $mpCat->id,
            'status' => 'active',
        ]);

        $this->supervisor = FactorySupervisor::create([
            'name' => 'John Supervisor',
            'phone' => '08012345678',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_redirects_to_batch_jobs_detail_page_when_production_batch_is_created()
    {
        $this->actingAs($this->admin);

        $test = Livewire::test(JobIndexPage::class)
            ->set('factory_supervisor_id', $this->supervisor->id)
            ->set('priority', 'Normal')
            ->set('notes', 'Test production batch creation redirect')
            ->set('batchProducts', [
                [
                    'manufacturing_product_id' => $this->mProduct->id,
                    'pattern_id' => null,
                    'planned_quantity' => 50,
                ],
            ])
            ->call('saveJob');

        $batch = \App\Models\ProductionBatch::latest()->first();
        $this->assertNotNull($batch);

        $test->assertRedirect(route('admin.production.batches.jobs', $batch->batch_code));
        $test->assertSessionHas('toast');
    }
}
