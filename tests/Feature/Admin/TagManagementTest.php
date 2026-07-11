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
            ->assertViewHas('categoryTree', function ($categoryTree) {
                return $categoryTree->count() === 1 && $categoryTree->first()->id === $this->category1->id;
            })
            ->set('categorySearch', 'Silk')
            ->assertViewHas('categoryTree', function ($categoryTree) {
                return $categoryTree->count() === 1 && $categoryTree->first()->id === $this->category2->id;
            })
            ->set('categorySearch', 'Nonexistent')
            ->assertViewHas('categoryTree', function ($categoryTree) {
                return $categoryTree->isEmpty();
            });
    }

    public function test_toggling_parent_category_selects_all_descendants()
    {
        // Create parent category and child category
        $parentCategory = Category::create([
            'name' => 'Clothing',
            'slug' => 'clothing',
            'is_active' => true,
            'is_leaf' => false,
        ]);

        $childCategory1 = Category::create([
            'name' => 'Pants',
            'slug' => 'pants',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
            'is_leaf' => true,
        ]);

        $childCategory2 = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
            'is_leaf' => true,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(TagIndexPage::class)
            // Initially empty
            ->assertSet('selectedCategoryIds', [])
            // Toggle parent category (should select parent and both child categories)
            ->call('toggleCategorySelection', $parentCategory->id)
            ->assertSet('selectedCategoryIds', [$parentCategory->id, $childCategory1->id, $childCategory2->id])
            // Toggle again (should deselect all of them)
            ->call('toggleCategorySelection', $parentCategory->id)
            ->assertSet('selectedCategoryIds', []);
    }

    public function test_tag_assigned_to_parent_category_is_available_for_descendant_products()
    {
        // Create parent category and child category
        $parentCategory = Category::create([
            'name' => 'Clothing Parent',
            'slug' => 'clothing-parent',
            'is_active' => true,
            'is_leaf' => false,
        ]);

        $childCategory = Category::create([
            'name' => 'Jeans Child',
            'slug' => 'jeans-child',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
            'is_leaf' => true,
        ]);

        // Create a tag and associate with parent category
        $tag = Tag::create([
            'name' => 'Denim Tag',
            'slug' => 'denim-tag',
        ]);
        $tag->categories()->attach($parentCategory->id);

        // In ProductIndexPage under child category, the tag should be available
        Livewire::actingAs($this->superAdmin)
            ->test(\App\Livewire\Admin\Products\ProductIndexPage::class, ['currentCategoryId' => $childCategory->id])
            ->assertViewHas('availableTags', function ($availableTags) use ($tag) {
                return $availableTags->contains('id', $tag->id);
            });
    }
}
