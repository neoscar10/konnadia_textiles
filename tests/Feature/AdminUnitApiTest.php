<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UnitGroup;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminUnitApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin = User::factory()->create([
            'email' => 'api_unit_admin@konnadia.com',
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');

        $this->token = JWTAuth::fromUser($this->admin);

        $this->seed(\Database\Seeders\UnitManagementSeeder::class);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_api_can_list_unit_groups(): void
    {
        $response = $this->getJson('/api/v1/admin/units/groups', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'description', 'is_active', 'units_count', 'base_unit', 'units']
                ]
            ]);
    }

    public function test_api_can_create_unit_group(): void
    {
        $payload = [
            'name' => 'Pressure Units',
            'code' => 'pressure',
            'description' => 'Units for measuring pressure',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/admin/units/groups', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'PRESSURE');

        $this->assertDatabaseHas('unit_groups', [
            'code' => 'PRESSURE',
            'name' => 'Pressure Units',
        ]);
    }

    public function test_api_can_show_and_update_unit_group(): void
    {
        $group = UnitGroup::where('code', 'LENGTH')->first();
        $this->assertNotNull($group);

        // Show
        $showRes = $this->getJson("/api/v1/admin/units/groups/{$group->id}", $this->authHeaders());
        $showRes->assertStatus(200)
            ->assertJsonPath('data.code', 'LENGTH');

        // Update
        $updateRes = $this->putJson("/api/v1/admin/units/groups/{$group->id}", [
            'name' => 'Length & Distance Units',
        ], $this->authHeaders());

        $updateRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Length & Distance Units');
    }

    public function test_api_can_toggle_unit_group_status(): void
    {
        $group = UnitGroup::create([
            'name' => 'Temp Group',
            'code' => 'TEMP',
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/v1/admin/units/groups/{$group->id}/toggle-status", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);
    }

    public function test_api_can_list_and_filter_unit_records(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();

        $response = $this->getJson("/api/v1/admin/units?unit_group_id={$lengthGroup->id}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'unit_group_id', 'name', 'short_code', 'is_base', 'ratio_to_base', 'relationship_statement', 'explanation']
                ]
            ]);
    }

    public function test_api_can_create_unit_record(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();

        $payload = [
            'unit_group_id' => $lengthGroup->id,
            'name' => 'petermeter',
            'short_code' => 'PM',
            'is_base' => false,
            'ratio_to_base' => 1000,
        ];

        $response = $this->postJson('/api/v1/admin/units', $payload, $this->authHeaders());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'petermeter')
            ->assertJsonPath('data.relationship_statement', '1 petermeter (PM) = 1,000 Meters (m)');

        $this->assertDatabaseHas('units', [
            'unit_group_id' => $lengthGroup->id,
            'name' => 'petermeter',
            'short_code' => 'PM',
            'ratio_to_base' => 1000,
        ]);
    }

    public function test_api_can_set_base_unit(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        $cm = Unit::where('unit_group_id', $lengthGroup->id)->where('short_code', 'cm')->first();
        $this->assertNotNull($cm);

        $response = $this->postJson("/api/v1/admin/units/{$cm->id}/set-base", [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_base', true)
            ->assertJsonPath('data.ratio_to_base', 1);

        // Confirm old base unit (Meters) is no longer base
        $meters = Unit::where('unit_group_id', $lengthGroup->id)->where('short_code', 'm')->first();
        $this->assertFalse((bool)$meters->is_base);
    }

    public function test_api_prevents_deleting_base_unit_when_group_has_other_units(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();
        $meters = Unit::where('unit_group_id', $lengthGroup->id)->where('is_base', true)->first();
        $this->assertNotNull($meters);

        $response = $this->deleteJson("/api/v1/admin/units/{$meters->id}", [], $this->authHeaders());

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cannot delete the Base Unit of a group. Set another unit as base first.');
    }

    public function test_api_can_convert_quantities(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();

        // 1 Meter to Centimeters
        $payload = [
            'from_unit' => 'Meters',
            'to_unit' => 'Centimeters',
            'quantity' => 2.5,
            'unit_group_id' => $lengthGroup->id,
        ];

        $response = $this->postJson('/api/v1/admin/units/convert', $payload, $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.output.quantity', 250);
    }

    public function test_api_can_preview_unit_relationship(): void
    {
        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();

        $payload = [
            'unit_group_id' => $lengthGroup->id,
            'name' => 'petermeter',
            'short_code' => 'PM',
            'is_base' => false,
            'ratio_to_base' => 600,
        ];

        $response = $this->postJson('/api/v1/admin/units/preview-relationship', $payload, $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.primary', '1 petermeter (PM) = 600 Meters (m)')
            ->assertJsonPath('data.explanation', 'Every 1 petermeter (PM) used in manufacturing or stock counts as 600 Meters (m)');
    }
}
