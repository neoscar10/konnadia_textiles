<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryBatchLog;
use App\Models\UnitGroup;
use App\Livewire\Factory\RawMaterialDetail;
use App\Livewire\Factory\InventoryBatchDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Livewire\Livewire;

class RawMaterialDetailAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected RawMaterialCategory $category;
    protected RawMaterial $material;
    protected InventoryBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::firstOrCreate(['name' => 'super_admin']);
        $this->admin = User::factory()->create(['email' => 'audit_admin@konnadia.com']);
        $this->admin->assignRole('super_admin');

        $this->seed(\Database\Seeders\UnitManagementSeeder::class);

        $lengthGroup = UnitGroup::where('code', 'LENGTH')->first();

        $this->category = RawMaterialCategory::create([
            'name' => 'Fabrics',
            'code' => 'CAT-FABRIC-AUDIT',
            'unit_group_id' => $lengthGroup->id,
            'is_active' => true,
        ]);

        $this->material = RawMaterial::create([
            'name' => 'Audit Cotton Linen',
            'raw_material_category_id' => $this->category->id,
            'unit_group_id' => $lengthGroup->id,
            'unit' => 'Meters',
            'standard_width' => 58.0,
            'width_unit' => 'Inch',
            'is_active' => true,
        ]);

        $this->batch = InventoryBatch::create([
            'raw_material_id' => $this->material->id,
            'supplier_name' => 'Apex Textiles Ltd',
            'purchase_date' => '2026-08-01',
            'invoice_number' => 'INV-9001',
            'quantity_received' => 500.0,
            'balance_quantity' => 350.0,
            'quantity_consumed' => 150.0,
            'purchase_rate' => 120.0,
            'total_amount' => 60000.0,
            'unit' => 'Meters',
            'status' => 'active',
        ]);

        InventoryBatchLog::create([
            'inventory_batch_id' => $this->batch->id,
            'user_id' => $this->admin->id,
            'action' => 'created',
            'quantity' => 500.0,
            'description' => 'Initial purchase stock created',
        ]);
    }

    public function test_raw_material_detail_page_renders_with_correct_stock_and_batches(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('factory.raw-materials.show', ['material' => $this->material->id]));
        $response->assertStatus(200);

        Livewire::test(RawMaterialDetail::class, ['material' => $this->material])
            ->assertSee('Audit Cotton Linen')
            ->assertSee('350.00') // balance qty
            ->assertSee('Apex Textiles Ltd')
            ->assertSee('INV-9001')
            ->call('setTab', 'logs')
            ->assertSee('Initial purchase stock created');
    }

    public function test_inventory_batch_detail_page_renders_with_logs(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('factory.raw-materials.batches.show', ['batch' => $this->batch->id]));
        $response->assertStatus(200);

        Livewire::test(InventoryBatchDetail::class, ['batch' => $this->batch])
            ->assertSee($this->batch->batch_number)
            ->assertSee('Apex Textiles Ltd')
            ->assertSee('Initial purchase stock created')
            ->assertSee('350.00');
    }
}
