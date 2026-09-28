<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CustomizedProductionOrder;
use App\Models\FactorySupervisor;
use App\Models\InventoryBatch;
use App\Models\JobWastage;
use App\Models\Labor;
use App\Models\ManufacturingProduct;
use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use App\Models\RawMaterialCategory;
use App\Models\SpareProduct;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBackendIssuesAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roleApi = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $roleWeb = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access production', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access orders', 'guard_name' => 'api']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'access categories', 'guard_name' => 'api']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole($roleApi);
        $this->admin->assignRole($roleWeb);
    }

    /**
     * Test 1: GET /api/v1/admin/orders/summary returns 200 OK stats json
     */
    public function test_issue_01_orders_summary_endpoint_exists()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/orders/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test 2: GET /api/v1/admin/labor/payroll/summary returns valid non-empty JSON
     */
    public function test_issue_02_labor_payroll_summary_returns_valid_json()
    {
        Labor::create([
            'name' => 'Test Laborer',
            'code' => 'LBR-100',
            'status' => true,
            'payment_method' => 'monthly_salary',
            'monthly_salary' => 15000,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/labor/payroll/summary');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_laborers', 1)
            ->assertJsonPath('data.monthly_salary_obligations', 15000);
    }

    /**
     * Test 3: GET /api/v1/admin/production/customized returns 200 OK list
     */
    public function test_issue_03_customized_production_endpoint_exists()
    {
        CustomizedProductionOrder::create([
            'item_description' => 'Custom Pillow Cover 24x24',
            'width' => 24,
            'length' => 24,
            'length_unit' => 'Inch',
            'target_quantity' => 50,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/customized');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_count', 1)
            ->assertJsonPath('data.0.item_description', 'Custom Pillow Cover 24x24');
    }

    /**
     * Test 4: GET /api/v1/admin/production/jobs/options returns 200 OK without SQL errors
     */
    public function test_issue_04_production_jobs_options_returns_valid_data()
    {
        ManufacturingProduct::create([
            'name' => 'Cotton Bedsheet',
            'code' => 'MP-BED-001',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/jobs/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.manufacturing_products.0.title', 'Cotton Bedsheet');
    }

    /**
     * Test 5 & 11: Category model and resources populate title attribute cleanly from name
     */
    public function test_issue_05_and_11_category_title_populated_from_name()
    {
        $category = Category::create([
            'name' => 'Home Furnishing',
            'slug' => 'home-furnishing',
            'is_active' => true,
        ]);

        $this->assertEquals('Home Furnishing', $category->title);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson("/api/v1/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Home Furnishing')
            ->assertJsonPath('data.title', 'Home Furnishing');
    }

    /**
     * Test 6: Spare product resource formats production_batch_id as batch code string
     */
    public function test_issue_06_spare_product_batch_id_formatted_as_batch_code_string()
    {
        $batch = ProductionBatch::create([
            'batch_code' => 'PB-2026-9999',
            'planned_quantity' => 100,
            'status' => 'in_progress',
        ]);

        $product = ManufacturingProduct::create([
            'name' => 'Spare Component',
            'code' => 'MP-SPARE-01',
            'status' => 'active',
        ]);

        $spare = SpareProduct::create([
            'production_batch_id' => $batch->id,
            'manufacturing_product_id' => $product->id,
            'quantity' => 10,
            'used_quantity' => 2,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/spare-products');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.production_batch_id', 'PB-2026-9999')
            ->assertJsonPath('data.0.production_batch_db_id', $batch->id);
    }

    /**
     * Test 7: Wastage log options returns manufacturing_product as nested object
     */
    public function test_issue_07_wastage_log_options_manufacturing_product_nested_object()
    {
        $product = ManufacturingProduct::create([
            'name' => 'Printed Satin Sheet',
            'code' => 'MP-SATIN-01',
            'status' => 'active',
        ]);

        ProductionJob::create([
            'job_code' => 'JOB-2026-8888',
            'production_batch_id' => 'PB-2026-0001',
            'manufacturing_product_id' => $product->id,
            'target_quantity' => 100,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/production/wastage-log/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recent_jobs.0.manufacturing_product.name', 'Printed Satin Sheet')
            ->assertJsonPath('data.recent_jobs.0.manufacturing_product.code', 'MP-SATIN-01');
    }

    /**
     * Test 8: Raw material purchase options recent_suppliers returns structured objects
     */
    public function test_issue_08_purchase_options_recent_suppliers_returns_objects()
    {
        $supplier = Supplier::create([
            'name' => 'Textile Traders Pvt Ltd',
            'contact_person' => 'Anil Sharma',
            'gstin' => '29ABCDE1234F1Z5',
        ]);

        $rmCat = RawMaterialCategory::create([
            'name' => 'Yarn',
            'code' => 'YARN',
        ]);

        $mat = RawMaterial::create([
            'name' => 'Raw Cotton Thread',
            'code' => 'RM-COTTON-01',
            'raw_material_category_id' => $rmCat->id,
            'unit' => 'Kg',
            'status' => true,
        ]);

        InventoryBatch::create([
            'raw_material_id' => $mat->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'purchase_date' => '2026-09-28',
            'invoice_number' => 'INV-999',
            'quantity_received' => 100,
            'balance_quantity' => 100,
            'base_quantity' => 100,
            'base_current_balance' => 100,
            'purchase_rate' => 250,
            'total_amount' => 25000,
            'unit' => 'Kg',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/admin/raw-materials/purchase/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.recent_suppliers.0.name', 'Textile Traders Pvt Ltd')
            ->assertJsonPath('data.recent_suppliers.0.gstin', '29ABCDE1234F1Z5');
    }

    /**
     * Test 9: Endpoints no longer return duplicate pagination keys
     */
    public function test_issue_09_no_duplicate_pagination_blocks()
    {
        FactorySupervisor::create([
            'name' => 'Supervisor John',
            'code' => 'SUP-01',
            'department' => 'Cutting',
            'status' => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/factory/supervisors?paginate=true');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('pagination')
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page', 'total_pages', 'count']]);
    }

    /**
     * Test 10: PATCH /admin/production/tasks/{id} accepts partial updates without required boolean fields
     */
    public function test_issue_10_patch_task_allows_partial_update()
    {
        $task = Task::create([
            'name' => 'Standard Hemming Stage',
            'code' => 'HEM-01',
            'consumes_raw_material' => false,
            'is_labor_required' => true,
            'status' => true,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/admin/production/tasks/{$task->id}", [
                'sequence_number' => 5,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.sequence_number', 5);
    }
}
