<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobLaborAllocation;
use App\Models\ManufacturingProductCategory;
use App\Models\ManufacturingProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWageApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Task $cuttingTask;
    protected Task $stitchingTask;
    protected ManufacturingProduct $product;
    protected Labor $salaryLabor;
    protected Labor $pieceLabor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');
        $perm = \Spatie\Permission\Models\Permission::findOrCreate('access production', 'api');
        $this->admin->givePermissionTo($perm);

        $this->cuttingTask = Task::create([
            'name' => 'Cutting',
            'code' => 'TSK-001',
            'is_labor_required' => true,
            'status' => true,
        ]);

        $this->stitchingTask = Task::create([
            'name' => 'Stitching',
            'code' => 'TSK-002',
            'is_labor_required' => true,
            'status' => true,
        ]);

        $cat = ManufacturingProductCategory::create(['name' => 'Shirts', 'status' => true]);
        $this->product = ManufacturingProduct::create([
            'name' => 'Formal Shirt',
            'title' => 'Formal Shirt Title',
            'code' => 'MP-SHIRT-01',
            'product_code' => 'MP-SHIRT-01',
            'manufacturing_product_category_id' => $cat->id,
            'status' => 'active',
        ]);

        $this->salaryLabor = Labor::create([
            'name' => 'Rajesh Sharma',
            'code' => 'LAB-001',
            'mobile_number' => '9876543210',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 25000.00,
        ]);

        $this->pieceLabor = Labor::create([
            'name' => 'Amit Kumar',
            'code' => 'LAB-002',
            'mobile_number' => '9876543211',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        // Create allocations for piece rate labor
        JobLaborAllocation::create([
            'production_batch_id' => 'PB-2026-0001',
            'job_id' => 'JOB-2026-0001',
            'labor_id' => $this->pieceLabor->id,
            'manufacturing_product_id' => $this->product->id,
            'task_id' => $this->cuttingTask->id,
            'quantity_processed' => 100,
            'base_rate' => 12.50,
            'bonus_rate' => 1.50,
            'calculated_wage' => 1400.00,
        ]);
    }

    /** @test */
    public function guest_cannot_access_wages_api()
    {
        $response = $this->getJson('/api/v1/admin/wages');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_admin_can_get_wage_summary()
    {
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/wages/summary');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'labor_counts' => [
                        'total',
                        'active',
                        'inactive',
                        'monthly_salary_workers',
                        'job_work_workers',
                    ],
                    'financials' => [
                        'total_pieces_processed',
                        'total_piece_rate_wages_earned',
                        'total_monthly_salary_obligations',
                        'total_gross_payroll',
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_list_worker_wages_with_period_and_method_filters()
    {
        $response = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/wages?payment_method=job_work');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('summary.total_workers', 1)
            ->assertJsonPath('data.0.worker.name', 'Amit Kumar');
    }

    /** @test */
    public function can_get_worker_wages_detail_ledger()
    {
        $response = $this->actingAs($this->admin, 'api')->getJson("/api/v1/admin/wages/worker/{$this->pieceLabor->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('earnings_summary.total_pieces_processed', 100)
            ->assertJsonPath('earnings_summary.piece_rate_earnings', 1400)
            ->assertJsonPath('data.0.job_code', 'JOB-2026-0001');
    }

    /** @test */
    public function can_fetch_tracking_history_and_options()
    {
        $optionsResponse = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/production/tracking-history/options');
        $optionsResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['workers', 'jobs', 'tasks', 'payment_methods']]);

        $historyResponse = $this->actingAs($this->admin, 'api')->getJson('/api/v1/admin/production/tracking-history');
        $historyResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('summary.total_allocations', 1)
            ->assertJsonPath('data.0.job_code', 'JOB-2026-0001');
    }
}
