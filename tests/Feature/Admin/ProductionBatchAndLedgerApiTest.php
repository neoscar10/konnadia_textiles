<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Task;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBatchAndLedgerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProduct $mfgProduct;
    protected ProductionBatch $batch;

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
        $this->admin->givePermissionTo('access production');

        $task = Task::create([
            'code' => 'TSK-FIN-01',
            'name' => 'Finishing',
            'sequence_number' => 1,
            'status' => true,
        ]);

        $this->mfgProduct = ManufacturingProduct::create([
            'product_code' => 'MP-PIL-001',
            'name' => 'Silk Pillow Cover',
            'title' => 'Silk Pillow Cover',
            'length' => 18,
            'width' => 28,
            'unit' => 'inch',
            'status' => true,
        ]);

        $this->mfgProduct->tasks()->attach($task->id, ['sequence_number' => 1]);

        $this->batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-8801',
            'manufacturing_product_id' => $this->mfgProduct->id,
            'planned_quantity' => 50,
            'status' => 'Completed',
            'supervisor_id' => $this->admin->id,
            'batch_date' => now()->format('Y-m-d'),
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-2026-8801',
            'production_batch_id' => $this->batch->batch_code,
            'production_batch_db_id' => $this->batch->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'task_id' => $task->id,
            'supervisor_id' => $this->admin->id,
            'target_quantity' => 50,
            'completed_quantity' => 50,
            'remaining_unconverted_quantity' => 50,
            'status' => 'completed',
        ]);
    }

    public function test_super_admin_can_create_production_batch()
    {
        $payload = [
            'manufacturing_product_id' => $this->mfgProduct->id,
            'planned_quantity' => 200,
            'notes' => 'Batch creation test via API',
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/admin/production/batches', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('production_batches', [
            'manufacturing_product_id' => $this->mfgProduct->id,
            'planned_quantity' => 200,
        ]);
    }

    public function test_super_admin_can_fetch_batch_jobs_and_ledger()
    {
        $responseJobs = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/batches/' . $this->batch->batch_code . '/jobs');

        $responseJobs->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('batch.batch_code', $this->batch->batch_code);

        $responseLedger = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/batches/' . $this->batch->id . '/ledger');

        $responseLedger->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.batch_id', $this->batch->id);
    }
}
