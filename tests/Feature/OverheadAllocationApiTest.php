<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Labor;
use App\Models\MonthlyOverheadAllocation;
use App\Models\MonthlyOverheadMaterialItem;
use App\Models\MonthlyOverheadOtherItem;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverheadAllocationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected RawMaterial $stitchingMaterial;

    protected function setUp(): void
    {
        parent::setUp();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);

        $this->adminUser = User::factory()->create([
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('admin');
        $this->adminUser->givePermissionTo('access production');

        $catStitch = RawMaterialCategory::create([
            'name' => 'Stitching Accessories',
            'code' => 'CAT-STITCH',
        ]);

        $this->stitchingMaterial = RawMaterial::create([
            'name' => 'Stitching Thread Reel',
            'code' => 'RM-ST-001',
            'raw_material_category_id' => $catStitch->id,
            'unit' => 'Rolls',
            'unit_cost' => 15.00,
            'opening_stock_quantity' => 100.00,
            'is_active' => true,
        ]);

        Labor::create([
            'name' => 'John Supervisor',
            'mobile_number' => '1234567890',
            'payment_method' => 'salary',
            'monthly_salary' => 25000.00,
            'status' => true,
        ]);
    }

    public function test_guest_cannot_access_overhead_allocation()
    {
        $response = $this->getJson('/api/v1/factory/overhead-allocation');
        $response->assertStatus(401);
    }

    public function test_options_returns_other_category_options()
    {
        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/factory/overhead-allocation/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['other_category_options']]);
    }

    public function test_show_calculates_unpersisted_month_data()
    {
        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/factory/overhead-allocation?year=2026&month=9');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.year', 2026)
            ->assertJsonPath('data.month', 9)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.salaried_staff_total', 25000);
    }

    public function test_store_saves_overhead_allocation()
    {
        $payload = [
            'year' => 2026,
            'month' => 9,
            'production_value' => 500000.00,
            'material_rows' => [
                [
                    'raw_material_id' => $this->stitchingMaterial->id,
                    'closing_stock_qty' => 80.00,
                ],
            ],
            'other_overhead_rows' => [
                [
                    'category' => 'Electricity & Utilities',
                    'custom_category' => '',
                    'amount' => 5000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser, 'api')
            ->postJson('/api/v1/factory/overhead-allocation', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'saved')
            ->assertJsonPath('data.production_value', 500000)
            ->assertJsonPath('data.other_overheads_total', 5000);

        $this->assertDatabaseHas('monthly_overhead_allocations', [
            'year' => 2026,
            'month' => 9,
            'production_value' => 500000.00,
            'other_overheads_total' => 5000.00,
            'status' => 'saved',
        ]);
    }

    public function test_history_lists_saved_allocations()
    {
        MonthlyOverheadAllocation::create([
            'year' => 2026,
            'month' => 8,
            'period_date' => '2026-08-01',
            'production_value' => 400000.00,
            'stitching_material_total' => 1000.00,
            'salaried_staff_total' => 20000.00,
            'other_overheads_total' => 3000.00,
            'total_overhead' => 24000.00,
            'overhead_percentage' => 6.00,
            'status' => 'saved',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/factory/overhead-allocation/history');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.year', 2026)
            ->assertJsonPath('data.0.month', 8);
    }

    public function test_alias_endpoints_work()
    {
        $response1 = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/production/overhead-allocation/options');
        $response1->assertStatus(200);

        $response2 = $this->actingAs($this->adminUser, 'api')
            ->getJson('/api/v1/admin/production/overhead-allocation/options');
        $response2->assertStatus(200);
    }
}
