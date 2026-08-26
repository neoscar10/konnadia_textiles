<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Labor;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\JobLaborAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrackingHistoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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

        $laborer = Labor::create([
            'name' => 'Suresh Master',
            'code' => 'LBR-002',
            'status' => true,
            'payment_method' => 'job_work',
        ]);

        $task = Task::create(['name' => 'Cutting', 'code' => 'CUT-01', 'status' => true]);
        $product = ManufacturingProduct::create(['name' => 'Bed Sheet Cotton', 'code' => 'MP-SHEET-01', 'status' => 'active']);

        JobLaborAllocation::create([
            'job_id' => 'JOB-2026-0005',
            'production_batch_id' => 'BATCH-2026-0005',
            'labor_id' => $laborer->id,
            'task_id' => $task->id,
            'manufacturing_product_id' => $product->id,
            'assigned_quantity' => 200,
            'quantity_processed' => 200,
            'rate_per_piece' => 15.00,
            'calculated_wage' => 3000.00,
        ]);
    }

    public function test_can_fetch_tracking_history_options()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/tracking-history/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.workers.0.name', 'Suresh Master');
    }

    public function test_can_fetch_and_filter_tracking_history()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/tracking-history?search=Suresh');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_allocations', 1)
            ->assertJsonPath('summary.total_pieces_processed', 200)
            ->assertJsonPath('data.0.worker.name', 'Suresh Master');
    }
}
