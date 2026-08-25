<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DualUnitStorefrontConversionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Product $storefrontProduct;
    protected ProductionJob $completedJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);

        $mfgProduct = ManufacturingProduct::create([
            'name' => 'Manufacturing Sheet',
            'code' => 'MP-SHEET-001',
            'status' => 'active',
        ]);

        $task = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-CUT-001',
            'status' => true,
        ]);

        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-8888',
            'manufacturing_product_id' => $mfgProduct->id,
            'planned_quantity' => 100,
            'total_finished_quantity' => 100,
            'unconverted_quantity' => 100,
            'status' => 'Completed',
            'supervisor_id' => $this->admin->id,
            'batch_date' => now()->format('Y-m-d'),
        ]);

        $this->completedJob = ProductionJob::create([
            'job_code' => 'JOB-2026-8888',
            'production_batch_db_id' => $batch->id,
            'production_batch_id' => $batch->batch_code,
            'manufacturing_product_id' => $mfgProduct->id,
            'task_id' => $task->id,
            'target_quantity' => 100,
            'status' => 'completed',
            'converted_quantity' => 0,
        ]);

        // Storefront product with Unit 1 (Piece) and Unit 2 (Box = 10 Pcs)
        $this->storefrontProduct = Product::create([
            'title' => 'Luxury Bedsheet Set',
            'sku' => 'SKU-LUX-BED',
            'is_active' => true,
            'stock_quantity' => 0,
        ]);

        ProductUnit::create([
            'product_id' => $this->storefrontProduct->id,
            'level' => 1,
            'name' => 'Piece',
            'short_code' => 'Pc',
            'conversion_to_base' => 1.0,
        ]);

        ProductUnit::create([
            'product_id' => $this->storefrontProduct->id,
            'level' => 2,
            'name' => 'Box',
            'short_code' => 'Box',
            'conversion_to_base' => 10.0,
        ]);
    }

    public function test_converts_in_unit_2_boxes_multiplying_base_stock_and_component_deduction()
    {
        // Convert 2 Boxes (where 1 Box = 10 Pcs -> Total 20 Base Pcs)
        Livewire::test(\App\Livewire\Admin\Production\BatchJobsDetailPage::class, ['batchCode' => $this->completedJob->production_batch_id])
            ->set('target_product_id', $this->storefrontProduct->id)
            ->set('target_unit_level', 2) // Unit 2: Box
            ->set('target_sets_desired', 2) // 2 Boxes
            ->set('conversionComponents', [
                [
                    'production_job_id' => $this->completedJob->id,
                    'quantity_per_set' => 1,
                    'total_pieces_input' => 100,
                ]
            ])
            ->call('processConversion')
            ->assertHasNoErrors();

        // Storefront Stock should increase by 20 Base Pcs
        $this->storefrontProduct->refresh();
        $this->assertEquals(20, $this->storefrontProduct->stock_quantity);

        // Job converted quantity should update by 20 Pcs
        $this->completedJob->refresh();
        $this->assertEquals(20, $this->completedJob->converted_quantity);
    }
}
