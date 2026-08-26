<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\JobLaborAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLaborStatsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Labor $laborer;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('super_admin');

        $this->laborer = Labor::create([
            'name' => 'Ramesh Kumar',
            'code' => 'LBR-001',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        $task = Task::create(['name' => 'Stitching', 'code' => 'STITCH-01', 'status' => true]);
        $product = ManufacturingProduct::create(['name' => 'Silk Pillow Cover', 'code' => 'MP-PILLOW-01', 'status' => 'active']);

        JobLaborAllocation::create([
            'job_id' => 'JOB-2026-0001',
            'production_batch_id' => 'BATCH-2026-0001',
            'labor_id' => $this->laborer->id,
            'task_id' => $task->id,
            'manufacturing_product_id' => $product->id,
            'assigned_quantity' => 100,
            'quantity_processed' => 50,
            'rate_per_piece' => 12.50,
            'calculated_wage' => 625.00,
        ]);
    }

    public function test_can_fetch_worker_detail_stats_and_batch_breakdown()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/labor/{$this->laborer->id}/stats");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('worker.name', 'Ramesh Kumar')
            ->assertJsonPath('performance_metrics.total_pieces_processed', 50)
            ->assertJsonPath('performance_metrics.total_direct_wages', 625)
            ->assertJsonPath('performance_metrics.total_batches_count', 1)
            ->assertJsonPath('performance_metrics.batch_breakdown.0.batch_code', 'BATCH-2026-0001');
    }

    public function test_can_fetch_payroll_summary()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/labor/payroll/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_laborers', 1)
            ->assertJsonPath('data.piece_rate_wages_earned', 625);
    }
}
