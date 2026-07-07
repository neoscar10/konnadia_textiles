<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class HomeContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super_admin']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);

        $permission = Permission::firstOrCreate(['name' => 'access home-content', 'guard_name' => 'web']);
        $adminRole->givePermissionTo($permission);
    }

    public function test_guest_cannot_access_home_content_builder()
    {
        $response = $this->get('/admin/home-content');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_home_content_builder()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/admin/home-content');
        $response->assertRedirect(route('home'));
    }

    public function test_admin_can_access_home_content_builder()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/home-content');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_banner_section()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('createSection')
            ->set('sectionType', 'banner')
            ->set('sectionTitle', 'Summer Banner')
            ->set('sectionSubtitle', 'Up to 50% Off')
            ->set('sectionDisplayStyle', 'hero')
            ->set('sectionIsActive', true)
            ->set('bannerImage', UploadedFile::fake()->image('banner.jpg'))
            ->call('saveSection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('home_content_sections', [
            'type' => 'banner',
            'title' => 'Summer Banner',
            'display_style' => 'hero',
        ]);
    }

    public function test_admin_can_update_section()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = HomeContentSection::create([
            'type' => 'banner',
            'title' => 'Old Title',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        HomeContentItem::create([
            'home_content_section_id' => $section->id,
            'item_type' => 'banner',
            'image_path' => 'banners/existing.jpg',
            'sort_order' => 1,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('editSection', $section->id)
            ->set('sectionTitle', 'Updated Banner Title')
            ->call('saveSection')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('home_content_sections', [
            'id' => $section->id,
            'title' => 'Updated Banner Title',
        ]);
    }

    public function test_admin_can_toggle_section_status()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = HomeContentSection::create([
            'type' => 'category_slider',
            'title' => 'Featured Categories',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('toggleStatus', $section->id);

        $this->assertFalse((bool)$section->fresh()->is_active);
    }

    public function test_admin_can_delete_section()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section = HomeContentSection::create([
            'type' => 'image_slider',
            'title' => 'Slider to Delete',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('confirmDelete', $section->id)
            ->assertSet('confirmingDeletionId', $section->id)
            ->call('deleteSection');

        $this->assertSoftDeleted('home_content_sections', [
            'id' => $section->id,
        ]);
    }

    public function test_admin_can_reorder_sections()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $section1 = HomeContentSection::create([
            'type' => 'banner',
            'title' => 'Banner 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $section2 = HomeContentSection::create([
            'type' => 'product_slider',
            'title' => 'Products 2',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('updateOrder', [$section2->id, $section1->id]);

        $this->assertEquals(0, $section2->refresh()->sort_order);
        $this->assertEquals(1, $section1->refresh()->sort_order);
    }
}
