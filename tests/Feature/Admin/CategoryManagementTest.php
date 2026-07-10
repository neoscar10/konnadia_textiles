<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use App\Services\Catalog\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Categories\CategoryIndexPage;
use Spatie\Permission\Models\Role;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $user;
    private CategoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($customerRole);

        $this->service = app(CategoryService::class);
    }

    public function test_guest_cannot_access_categories_page()
    {
        $response = $this->get('/admin/categories');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_categories_page()
    {
        $response = $this->actingAs($this->user)->get('/admin/categories');
        $response->assertRedirect(route('home'));
    }

    public function test_super_admin_can_access_categories_page()
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/categories');
        $response->assertSuccessful();
    }

    public function test_category_can_be_created_at_root_level()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class)
            ->set('form.name', 'Men\'s Wear')
            ->set('form.description', 'All menswear clothing')
            ->set('form.is_active', true)
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Men\'s Wear',
            'parent_id' => null,
            'slug' => 'mens-wear',
            'is_active' => 1,
        ]);
    }

    public function test_sub_category_can_be_created_under_parent()
    {
        $parent = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class, ['currentCategoryId' => $parent->id])
            ->set('form.name', 'Shirts')
            ->set('form.description', 'Men\'s shirts')
            ->set('form.is_active', true)
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'name' => 'Shirts',
            'parent_id' => $parent->id,
            'slug' => 'shirts',
        ]);
    }

    public function test_duplicate_category_name_under_same_parent_is_blocked()
    {
        $parent = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
        ]);

        $this->service->create([
            'name' => 'Shirts',
            'parent_id' => $parent->id,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class, ['currentCategoryId' => $parent->id])
            ->set('form.name', 'Shirts')
            ->call('saveCategory')
            ->assertHasErrors(['form.name']);
    }

    public function test_category_can_be_updated()
    {
        $category = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
            'description' => 'Original description',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class)
            ->call('editCategory', $category->id)
            ->set('form.name', 'Men\'s Fashion')
            ->set('form.description', 'Updated description')
            ->call('saveCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Men\'s Fashion',
            'description' => 'Updated description',
            'slug' => 'mens-fashion',
        ]);
    }

    public function test_category_status_can_be_toggled()
    {
        $category = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class)
            ->call('toggleCategoryStatus', $category->id);

        $this->assertFalse((bool) $category->fresh()->is_active);
    }

    public function test_category_without_children_can_be_deleted()
    {
        $category = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class)
            ->call('confirmDeleteCategory', $category->id)
            ->call('deleteCategory');

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_category_with_children_cascade_deleted()
    {
        $parent = $this->service->create([
            'name' => 'Men\'s Wear',
            'parent_id' => null,
        ]);

        $child = $this->service->create([
            'name' => 'Shirts',
            'parent_id' => $parent->id,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(CategoryIndexPage::class)
            ->call('confirmDeleteCategory', $parent->id)
            ->call('deleteCategory')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('categories', [
            'id' => $parent->id,
        ]);

        $this->assertSoftDeleted('categories', [
            'id' => $child->id,
        ]);
    }
}
