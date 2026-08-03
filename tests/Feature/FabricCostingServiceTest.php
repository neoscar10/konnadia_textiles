<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\RawMaterialCategory;
use App\Models\RawMaterial;
use App\Models\InventoryBatch;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\Task;
use App\Services\FabricCostingService;
use App\Livewire\Admin\Production\JobDetailPage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FabricCostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $fabricCat;
    protected RawMaterial $fabricMat;
    protected InventoryBatch $fabricBatch;
    protected ManufacturingProductCategory $mpCat;
    protected ManufacturingProduct $mpBedSheet;
    protected ManufacturingProduct $mpPillowCover;
    protected ProductionBatch $prodBatch;
    protected ProductionJob $prodJob;
    protected Task $cuttingTask;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Setup raw material category and material
        $this->fabricCat = RawMaterialCategory::create([
            'name' => 'Fabric Materials',
            'code' => 'CAT-FAB',
        ]);

        $this->fabricMat = RawMaterial::create([
            'name' => 'Premium Cotton',
            'code' => 'RAW-COT-001',
            'raw_material_category_id' => $this->fabricCat->id,
            'unit' => 'Meters',
        ]);

        // Setup fabric roll batch
        $this->fabricBatch = InventoryBatch::create([
            'raw_material_id' => $this->fabricMat->id,
            'batch_number' => 'BAT-FAB-001',
            'received_quantity' => 100.00,
            'initial_quantity' => 100.00,
            'balance_quantity' => 100.00,
            'unit_cost' => 150.00, // ₹150 per meter
            'unit' => 'Meters',
        ]);

        // Setup manufacturing product category and products
        $this->mpCat = ManufacturingProductCategory::create([
            'name' => 'Bedding',
            'status' => true,
        ]);

        $this->mpBedSheet = ManufacturingProduct::create([
            'name' => 'King Bed Sheet',
            'manufacturing_product_category_id' => $this->mpCat->id,
            'is_fabric_used' => true,
            'standard_fabric_width' => 90.00,
            'standard_fabric_length' => 2.75,
            'fabric_width_unit' => 'inch',
            'fabric_length_unit' => 'meter',
            'status' => 'active',
        ]);

        $this->mpPillowCover = ManufacturingProduct::create([
            'name' => 'Standard Pillow Cover',
            'manufacturing_product_category_id' => $this->mpCat->id,
            'is_fabric_used' => true,
            'standard_fabric_width' => 20.00,
            'standard_fabric_length' => 30.00,
            'fabric_width_unit' => 'inch',
            'fabric_length_unit' => 'inch',
            'status' => 'active',
        ]);

        // Setup Task
        $this->cuttingTask = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-001',
            'cost_type' => 'piece_rate',
        ]);

        // Setup production batch & job
        $this->prodBatch = ProductionBatch::create([
            'batch_code' => 'PB-2026-0001',
            'status' => 'scheduled',
            'supervisor_id' => $this->admin->id,
            'manufacturing_product_id' => $this->mpBedSheet->id,
            'planned_quantity' => 10,
        ]);

        $this->prodJob = ProductionJob::create([
            'job_code' => 'JOB-2026-0001',
            'production_batch_db_id' => $this->prodBatch->id,
            'manufacturing_product_id' => $this->mpBedSheet->id,
            'target_quantity' => 10,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_correctly_normalizes_units_to_inches()
    {
        $service = new FabricCostingService();

        // 1 inch = 1 inch
        $this->assertEquals(10.0, $service->convertToInches(10.0, 'inch'));
        // 1 cm = 0.393701 inch
        $this->assertEquals(10.0 * 0.393701, $service->convertToInches(10.0, 'cm'));
        // 1 meter = 39.3701 inch
        $this->assertEquals(2.0 * 39.3701, $service->convertToInches(2.0, 'meter'));
        // 1 yard = 36 inches
        $this->assertEquals(3.0 * 36.0, $service->convertToInches(3.0, 'yard'));
    }

    /** @test */
    public function it_calculates_surface_areas_correctly()
    {
        $service = new FabricCostingService();

        // 20 inch width by 30 inch length
        $areaInches = $service->calculateArea(20.0, 'inch', 30.0, 'inch');
        $this->assertEquals(600.0, $areaInches);

        // 90 inch width by 2.75 meter length
        $expectedArea = 90.0 * (2.75 * 39.3701);
        $this->assertEquals($expectedArea, $service->calculateArea(90.0, 'inch', 2.75, 'meter'));
    }

    /** @test */
    public function it_allocates_fabric_base_cost_and_wastage_cost_accurately()
    {
        $service = new FabricCostingService();

        // Consumed length: 10 meters of premium cotton. Unit Cost = ₹150. Total = ₹1500.
        // Wastage: 1 meter. Wastage Cost = ₹150.
        // Fabric Width: 60 inches.
        // Total consumed area = 60 inches * (10 meters * 39.3701 inches/meter) = 23622.06 sq inches.
        
        $cutPieces = [
            [
                'manufacturing_product_id' => $this->mpBedSheet->id,
                'width' => 90.00,
                'length' => 2.75,
                'width_unit' => 'inch',
                'length_unit' => 'meter',
                'quantity' => 2,
            ],
            [
                'manufacturing_product_id' => $this->mpPillowCover->id,
                'width' => 20.00,
                'length' => 30.00,
                'width_unit' => 'inch',
                'length_unit' => 'inch',
                'quantity' => 10,
            ]
        ];

        $result = $service->calculateFabricCostAllocation(
            $this->prodJob->id,
            $this->fabricBatch->id,
            10.0, // consumed
            $cutPieces,
            1.0,  // wastage
            60.0  // fabric roll width
        );

        $this->assertEquals(1500.0, $result['total_fabric_cost_consumed']);
        $this->assertEquals(150.0, $result['total_wastage_cost']);

        // Verify databases tables got updated
        $this->assertDatabaseHas('job_material_consumptions', [
            'production_job_id' => $this->prodJob->id,
            'inventory_batch_id' => $this->fabricBatch->id,
            'consumed_length' => 10.0,
            'total_fabric_cost' => 1500.0,
        ]);

        $this->assertDatabaseHas('job_production_outputs', [
            'production_job_id' => $this->prodJob->id,
            'manufacturing_product_id' => $this->mpBedSheet->id,
            'quantity_produced' => 2,
        ]);

        $this->assertDatabaseHas('job_production_outputs', [
            'production_job_id' => $this->prodJob->id,
            'manufacturing_product_id' => $this->mpPillowCover->id,
            'quantity_produced' => 10,
        ]);
    }

    /** @test */
    public function it_integrates_cutting_session_terminal_via_livewire()
    {
        $this->actingAs($this->admin);

        Livewire::test(JobDetailPage::class, ['id' => $this->prodJob->id])
            ->set('selectedTaskId', $this->cuttingTask->id)
            ->set('cuttingFabricBatchId', $this->fabricBatch->id)
            ->set('cuttingConsumedLength', 5.0)
            ->set('cuttingWastageLength', 0.5)
            ->set('cuttingFabricWidth', 60.0)
            ->set('cuttingOutputs', [
                [
                    'manufacturing_product_id' => $this->mpBedSheet->id,
                    'width' => 90.00,
                    'length' => 2.75,
                    'width_unit' => 'inch',
                    'length_unit' => 'meter',
                    'quantity' => 1,
                ]
            ])
            ->call('saveCuttingSession')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        // Check if stock decremented
        $this->assertEquals(95.0, $this->fabricBatch->fresh()->balance_quantity);

        // Check if consumption and output saved
        $this->assertDatabaseHas('job_material_consumptions', [
            'production_job_id' => $this->prodJob->id,
            'inventory_batch_id' => $this->fabricBatch->id,
            'consumed_length' => 5.0,
        ]);
    }

    /** @test */
    public function it_prevents_excess_consumption_beyond_batch_balance()
    {
        $this->actingAs($this->admin);

        Livewire::test(JobDetailPage::class, ['id' => $this->prodJob->id])
            ->set('selectedTaskId', $this->cuttingTask->id)
            ->set('cuttingFabricBatchId', $this->fabricBatch->id)
            ->set('cuttingConsumedLength', 120.0) // batch only has 100
            ->set('cuttingWastageLength', 1.0)
            ->set('cuttingFabricWidth', 60.0)
            ->set('cuttingOutputs', [
                [
                    'manufacturing_product_id' => $this->mpBedSheet->id,
                    'width' => 90.00,
                    'length' => 2.75,
                    'width_unit' => 'inch',
                    'length_unit' => 'meter',
                    'quantity' => 1,
                ]
            ])
            ->call('saveCuttingSession')
            ->assertHasErrors(['cuttingConsumedLength']);
    }
}
