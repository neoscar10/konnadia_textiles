<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LaborCategory;
use App\Models\Task;
use App\Models\Labor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLaborCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        $this->admin = User::factory()->create([
            'is_active' => true,
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_can_list_and_search_labor_categories()
    {
        LaborCategory::create(['name' => 'Stitching Specialist', 'code' => 'STITCH-01', 'status' => true]);
        LaborCategory::create(['name' => 'Cutting Master', 'code' => 'CUT-01', 'status' => false]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/labor-categories?search=Stitching');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total_count' => 2,
                ],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Stitching Specialist', $response->json('data.0.name'));
    }

    public function test_can_fetch_labor_category_options()
    {
        LaborCategory::create(['name' => 'Packaging Team', 'code' => 'PKG-01', 'status' => true]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/labor-categories/options');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data.labor_categories')));
    }

    public function test_can_create_update_and_toggle_labor_category()
    {
        $payload = [
            'name' => 'Quality Inspector',
            'code' => 'QI-01',
            'description' => 'Final quality assurance and auditing team',
            'status' => true,
        ];

        $createResp = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/factory/labor-categories', $payload);

        $createResp->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Quality Inspector',
                    'code' => 'QI-01',
                ],
            ]);

        $categoryId = $createResp->json('data.id');

        $updateResp = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/factory/labor-categories/{$categoryId}", [
                'name' => 'Quality Inspector Lead',
                'code' => 'QI-01-LEAD',
                'description' => 'Updated QA description',
            ]);

        $updateResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Quality Inspector Lead',
                    'code' => 'QI-01-LEAD',
                ],
            ]);

        $toggleResp = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/factory/labor-categories/{$categoryId}/toggle-status");

        $toggleResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => false,
                ],
            ]);
    }

    public function test_deletion_protection_when_category_linked_to_tasks_or_workers()
    {
        $category = LaborCategory::create(['name' => 'Ironing Department', 'code' => 'IRON-01', 'status' => true]);

        Task::create([
            'name' => 'Steam Ironing',
            'code' => 'TASK-IRON',
            'is_labor_required' => true,
            'labor_category_id' => $category->id,
            'status' => true,
        ]);

        $deleteResp = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/factory/labor-categories/{$category->id}");

        $deleteResp->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertDatabaseHas('labor_categories', ['id' => $category->id]);
    }
}
