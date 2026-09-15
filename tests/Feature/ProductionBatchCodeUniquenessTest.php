<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected FactorySupervisor $supervisor;
    protected ManufacturingProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $mpCat = ManufacturingProductCategory::create(['name' => 'Bedsheet Category']);
        $this->product = ManufacturingProduct::create([
            'name' => 'Cotton Bedsheet',
            'code' => 'PROD-BS-001',
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
    public function it_automatically_resolves_batch_code_conflicts_without_integrity_violations()
    {
        $year = date('Y');

        // Create an existing batch with PB-2026-0001
        ProductionBatch::create([
            'batch_code' => "PB-{$year}-0001",
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 100,
            'supervisor_id' => $this->admin->id,
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'Created',
        ]);

        // Manually insert PB-2026-0002 to simulate out-of-order sequence
        ProductionBatch::create([
            'batch_code' => "PB-{$year}-0002",
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 100,
            'supervisor_id' => $this->admin->id,
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'Created',
        ]);

        // Now create a new batch without explicit batch_code - it should generate PB-2026-0003
        $newBatch = ProductionBatch::create([
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 200,
            'supervisor_id' => $this->admin->id,
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'Created',
        ]);

        $this->assertEquals("PB-{$year}-0003", $newBatch->batch_code);

        // Simulate collision: manually force a batch with PB-2026-0004
        ProductionBatch::create([
            'batch_code' => "PB-{$year}-0004",
            'manufacturing_product_id' => $this->product->id,
            'planned_quantity' => 150,
            'supervisor_id' => $this->admin->id,
            'factory_supervisor_id' => $this->supervisor->id,
            'status' => 'Created',
        ]);

        // Next generated batch code should bypass PB-2026-0004 and produce PB-2026-0005
        $nextBatchCode = ProductionBatch::generateNextBatchCode();
        $this->assertEquals("PB-{$year}-0005", $nextBatchCode);
    }
}
