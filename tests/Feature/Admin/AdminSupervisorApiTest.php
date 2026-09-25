<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\FactorySupervisor;
use App\Models\ProductionBatch;
use App\Models\ManufacturingProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminSupervisorApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected FactorySupervisor $supervisor;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $permProduction = Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permProduction]);

        $this->superAdmin = User::factory()->create(['email' => 'super_supervisor@konnadia.com']);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create(['email' => 'restricted_supervisor@konnadia.com']);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);

        $this->supervisor = FactorySupervisor::create([
            'name' => 'Ramesh Kumar',
            'code' => 'SUP-0001',
            'phone' => '+91 98765 43210',
            'email' => 'ramesh@factory.in',
            'department' => 'Cutting',
            'notes' => 'Senior floor supervisor',
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_supervisor_apis(): void
    {
        $response = $this->getJson('/api/v1/factory/supervisors');
        $response->assertStatus(401);
    }

    public function test_restricted_admin_without_permission_cannot_access_supervisor_apis(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->restrictedAdminToken)
            ->getJson('/api/v1/factory/supervisors');

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_authorized_admin_can_fetch_supervisors_listing_and_options(): void
    {
        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/factory/supervisors/options');

        $optionsResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.supervisors')
            ->assertJsonPath('data.supervisors.0.name', 'Ramesh Kumar')
            ->assertJsonPath('data.departments.0', 'Cutting');

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/factory/supervisors?search=Ramesh&status=active');

        $listResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Ramesh Kumar');

        // Test route aliases /api/v1/production/supervisors and /api/v1/admin/production/supervisors
        $prodListResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/production/supervisors');
        $prodListResp->assertStatus(200)->assertJsonPath('success', true);

        $adminListResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/production/supervisors');
        $adminListResp->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_authorized_admin_can_view_single_supervisor_details(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/factory/supervisors/' . $this->supervisor->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->supervisor->id)
            ->assertJsonPath('data.name', 'Ramesh Kumar')
            ->assertJsonPath('data.code', 'SUP-0001');

        $prodShowResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/production/supervisors/' . $this->supervisor->id);
        $prodShowResp->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_authorized_admin_can_create_supervisor_with_manual_and_auto_code(): void
    {
        // 1. Manual code creation
        $payload1 = [
            'name' => 'Priya Shah',
            'code' => 'SUP-0002',
            'phone' => '+91 91234 56789',
            'email' => 'priya@factory.in',
            'department' => 'Stitching',
            'notes' => 'Stitching supervisor',
            'is_active' => true,
        ];

        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/factory/supervisors', $payload1);

        $response1->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Priya Shah')
            ->assertJsonPath('data.code', 'SUP-0002');

        $this->assertDatabaseHas('factory_supervisors', [
            'name' => 'Priya Shah',
            'code' => 'SUP-0002',
        ]);

        // 2. Auto code creation (omitting code)
        $payload2 = [
            'name' => 'Amit Patel',
            'department' => 'QC',
        ];

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/factory/supervisors', $payload2);

        $response2->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Amit Patel');

        $this->assertDatabaseHas('factory_supervisors', [
            'name' => 'Amit Patel',
        ]);
    }

    public function test_authorized_admin_can_update_and_toggle_supervisor_status(): void
    {
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson('/api/v1/factory/supervisors/' . $this->supervisor->id, [
                'name' => 'Ramesh V. Kumar',
                'department' => 'Master Cutting',
            ]);

        $updateResp->assertStatus(200)
            ->assertJsonPath('data.name', 'Ramesh V. Kumar')
            ->assertJsonPath('data.department', 'Master Cutting');

        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/factory/supervisors/' . $this->supervisor->id . '/toggle-status');

        $toggleResp->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_authorized_admin_cannot_delete_supervisor_linked_to_batches(): void
    {
        $mfgProduct = ManufacturingProduct::create([
            'name' => 'Sample Product',
            'code' => 'PROD-SMPL-01',
            'status' => true,
        ]);

        ProductionBatch::create([
            'batch_code' => 'PB-2026-TEST',
            'manufacturing_product_id' => $mfgProduct->id,
            'factory_supervisor_id' => $this->supervisor->id,
            'planned_quantity' => 100,
            'target_quantity' => 100,
            'status' => 'in_progress',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/factory/supervisors/' . $this->supervisor->id);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('linked_batches_count', 1);

        $this->assertDatabaseHas('factory_supervisors', ['id' => $this->supervisor->id]);
    }

    public function test_authorized_admin_can_delete_unlinked_supervisor(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/factory/supervisors/' . $this->supervisor->id);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('factory_supervisors', ['id' => $this->supervisor->id]);
    }
}
