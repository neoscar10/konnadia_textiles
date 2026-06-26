<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\HomeContentSection;
use App\Models\HomeContentItem;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);
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
        $response->assertRedirect('/home');
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
            ->call('openCreateModal')
            ->set('sectionForm.type', 'banner')
            ->set('sectionForm.title', 'Summer Banner')
            ->set('sectionForm.subtitle', 'Up to 50% Off')
            ->set('sectionForm.display_style', 'hero')
            ->set('sectionForm.is_active', true)
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

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\HomeContent\HomeContentPage::class)
            ->call('editSection', $section->id)
            ->set('sectionForm.title', 'Updated Banner Title')
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
            ->call('toggleSectionStatus', $section->id);

        $this->assertDatabaseHas('home_content_sections', [
            'id' => $section->id,
            'is_active' => false,
        ]);
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
            ->call('confirmDeleteSection', $section->id)
            ->call('deleteSection');

        $this->assertDatabaseMissing('home_content_sections', [
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
            ->call('updateOrder', [
                ['id' => $section2->id, 'order' => 1],
                ['id' => $section1->id, 'order' => 2],
            ]);

        $this->assertEquals(1, $section2->refresh()->sort_order);
        $this->assertEquals(2, $section1->refresh()->sort_order);
    }
}
