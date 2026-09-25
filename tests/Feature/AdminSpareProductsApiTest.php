<?php

namespace Tests\Feature;

use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\SpareProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSpareProductsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProduct $mfgProduct;
    protected ProductionBatch $batch;
    protected ProductionJob $job;
    protected SpareProduct $spareProduct;

    protected function setUp(): void
    {
        parent::setUp();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access production');

        $this->mfgProduct = ManufacturingProduct::create([
            'name' => 'Pillow Cover Deluxe',
            'code' => 'MP-PCD-01',
            'status' => 'active',
        ]);

        $this->batch = ProductionBatch::create([
            'batch_code' => 'PB-SPARE-001',
            'planned_quantity' => 100,
            'status' => 'Completed',
        ]);

        $this->job = ProductionJob::create([
            'job_code' => 'JOB-SPARE-001',
            'production_batch_db_id' => $this->batch->id,
            'production_batch_id' => $this->batch->batch_code,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'target_quantity' => 100,
            'completed_quantity' => 100,
            'status' => 'completed',
        ]);

        $this->spareProduct = SpareProduct::create([
            'production_batch_id' => $this->batch->id,
            'production_job_id' => $this->job->id,
            'manufacturing_product_id' => $this->mfgProduct->id,
            'design_id' => 'DSG-FLORAL-RED',
            'quantity' => 10,
            'used_quantity' => 2,
            'notes' => 'Leftover from lot conversion',
        ]);
    }

    public function test_guest_cannot_access_spare_products_api(): void
    {
        $response = $this->getJson('/api/v1/admin/production/spare-products');
        $response->assertStatus(401);
    }

    public function test_can_fetch_spare_products_stats(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/spare-products/stats');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'total_spare_recorded',
                    'total_spare_used',
                    'total_available_spare',
                    'unique_design_ids_count',
                    'available_design_ids',
                ],
            ])
            ->assertJsonPath('data.total_spare_recorded', 10)
            ->assertJsonPath('data.total_spare_used', 2)
            ->assertJsonPath('data.total_available_spare', 8);
    }

    public function test_can_fetch_spare_products_options(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/spare-products/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['design_ids', 'manufacturing_products', 'status_options'],
            ]);
    }

    public function test_can_list_spare_products_with_filters(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/spare-products?search=DSG-FLORAL');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'summary' => ['total_spare_recorded', 'total_spare_used', 'total_available_spare'],
                'data',
                'meta',
            ])
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_spare_product_detail(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/production/spare-products/{$this->spareProduct->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->spareProduct->id)
            ->assertJsonPath('data.design_id', 'DSG-FLORAL-RED')
            ->assertJsonPath('data.available_quantity', 8);
    }

    public function test_cannot_delete_used_spare_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/production/spare-products/{$this->spareProduct->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_delete_unused_spare_product(): void
    {
        $unusedSpare = SpareProduct::create([
            'manufacturing_product_id' => $this->mfgProduct->id,
            'design_id' => 'DSG-UNUSED',
            'quantity' => 5,
            'used_quantity' => 0,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/admin/production/spare-products/{$unusedSpare->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('spare_products', ['id' => $unusedSpare->id]);
    }

    public function test_factory_namespace_alias_works(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/spare-products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
