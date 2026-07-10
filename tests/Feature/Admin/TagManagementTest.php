<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Tags\TagIndexPage;
use Spatie\Permission\Models\Role;

class TagManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Category $category1;
    private Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        // Create leaf categories
        $this->category1 = Category::create([
            'name' => 'Cotton Fabrics',
            'slug' => 'cotton-fabrics',
            'is_active' => true,
            'is_leaf' => true,
        ]);

        $this->category2 = Category::create([
            'name' => 'Silk Sarees',
            'slug' => 'silk-sarees',
            'is_active' => true,
            'is_leaf' => true,
        ]);
    }

    public function test_super_admin_can_access_tags_page()
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/tags');
        $response->assertSuccessful();
    }

    public function test_can_create_tag_with_categories()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(TagIndexPage::class)
            ->set('form.name', 'New Summer Tag')
            ->set('selectedCategoryIds', [$this->category1->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tags', [
            'name' => 'New Summer Tag',
            'slug' => 'new-summer-tag',
        ]);

        $tag = Tag::where('slug', 'new-summer-tag')->first();
        $this->assertTrue($tag->categories->contains($this->category1->id));
    }

    public function test_can_filter_categories_checklist_by_search()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(TagIndexPage::class)
            ->set('categorySearch', 'Cotton')
            ->assertViewHas('leafCategories', function ($leafCategories) {
                return $leafCategories->count() === 1 && $leafCategories->first()->id === $this->category1->id;
            })
            ->set('categorySearch', 'Silk')
            ->assertViewHas('leafCategories', function ($leafCategories) {
                return $leafCategories->count() === 1 && $leafCategories->first()->id === $this->category2->id;
            })
            ->set('categorySearch', 'Nonexistent')
            ->assertViewHas('leafCategories', function ($leafCategories) {
                return $leafCategories->isEmpty();
            });
    }
}
