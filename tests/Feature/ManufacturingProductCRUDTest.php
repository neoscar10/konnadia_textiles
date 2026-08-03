<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductCategory;
use App\Livewire\Factory\AddManufacturingProductForm;
use App\Livewire\Factory\ManufacturingProductList;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturingProductCRUDTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected ManufacturingProductCategory $activeCategory;
    protected ManufacturingProductCategory $inactiveCategory;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\FactoryRolesSeeder::class);

        // Create admin user and assign admin role
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Create categories
        $this->activeCategory = ManufacturingProductCategory::create([
            'name' => 'Active Category',
            'status' => true,
        ]);

        $this->inactiveCategory = ManufacturingProductCategory::create([
            'name' => 'Inactive Category',
            'status' => false,
        ]);

        // Create a default task for routing configuration auto-population
        \App\Models\Task::create([
            'name' => 'Cutting',
            'code' => 'CUT-01',
            'status' => true,
        ]);
    }

    /** @test */
    public function it_automatically_generates_sequential_product_codes()
    {
        $year = date('Y');

        $p1 = ManufacturingProduct::create([
            'name' => 'Product One',
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'active',
        ]);
        $this->assertEquals("MP-{$year}-0001", $p1->code);

        $p2 = ManufacturingProduct::create([
            'name' => 'Product Two',
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'active',
        ]);
        $this->assertEquals("MP-{$year}-0002", $p2->code);
    }

    /** @test */
    public function it_validates_form_inputs_and_prevents_linking_to_inactive_categories()
    {
        $this->actingAs($this->admin);

        Livewire::test(AddManufacturingProductForm::class)
            ->set('name', '')
            ->set('manufacturing_product_category_id', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required', 'manufacturing_product_category_id' => 'required']);

        Livewire::test(AddManufacturingProductForm::class)
            ->set('name', 'New Bed Sheet')
            ->set('manufacturing_product_category_id', $this->inactiveCategory->id)
            ->call('save')
            ->assertHasErrors(['manufacturing_product_category_id']);
    }

    /** @test */
    public function it_saves_new_products_successfully()
    {
        $this->actingAs($this->admin);
        $year = date('Y');

        Livewire::test(AddManufacturingProductForm::class)
            ->set('name', 'King Bed Sheet')
            ->set('manufacturing_product_category_id', $this->activeCategory->id)
            ->set('status', 'active')
            ->set('standard_labor_rate', 25.00)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('factory.products.index'));

        $this->assertDatabaseHas('manufacturing_products', [
            'name' => 'King Bed Sheet',
            'code' => "MP-{$year}-0001",
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'active',
            'standard_labor_rate' => 25.00,
        ]);
    }

    /** @test */
    public function it_lists_and_filters_products_correctly()
    {
        $this->actingAs($this->admin);

        $p1 = ManufacturingProduct::create([
            'name' => 'Target Product',
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'active',
        ]);

        $p2 = ManufacturingProduct::create([
            'name' => 'Other Item',
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'inactive',
        ]);

        // Search filter
        Livewire::test(ManufacturingProductList::class)
            ->set('search', 'Target')
            ->assertSee('Target Product')
            ->assertDontSee('Other Item');

        // Status filter
        Livewire::test(ManufacturingProductList::class)
            ->set('statusFilter', 'inactive')
            ->assertSee('Other Item')
            ->assertDontSee('Target Product');
    }

    /** @test */
    public function it_can_toggle_product_status()
    {
        $this->actingAs($this->admin);

        $product = ManufacturingProduct::create([
            'name' => 'Toggle Target',
            'manufacturing_product_category_id' => $this->activeCategory->id,
            'status' => 'active',
        ]);

        Livewire::test(ManufacturingProductList::class)
            ->call('toggleStatus', $product->id)
            ->assertDispatched('toast');

        $this->assertEquals('inactive', $product->fresh()->status);
    }
}
