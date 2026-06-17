<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CustomerLevel;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerLevelManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);
    }

    public function test_guest_cannot_access_customer_levels()
    {
        $response = $this->get('/admin/customer-levels');
        $response->assertRedirect('/login');
    }

    public function test_normal_user_cannot_access_customer_levels()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/admin/customer-levels');
        $response->assertRedirect('/home');
    }

    public function test_super_admin_can_access_customer_levels()
    {
        $this->withoutExceptionHandling();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/customer-levels');
        $response->assertStatus(200);
    }

    public function test_super_admin_can_create_customer_level()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->set('form.name', 'New Level')
            ->set('form.discount_percentage', 10)
            ->set('form.default_credit_limit', 50000)
            ->set('form.is_active', true)
            ->call('save')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('customer_levels', [
            'name' => 'New Level',
            'discount_percentage' => 10,
        ]);
    }

    public function test_validation_fails_when_name_is_missing()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->set('form.name', '')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required']);
    }

    public function test_validation_fails_when_discount_is_above_100()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->set('form.name', 'Bad Discount Level')
            ->set('form.discount_percentage', 150)
            ->call('save')
            ->assertHasErrors(['form.discount_percentage' => 'max']);
    }

    public function test_super_admin_can_update_customer_level()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $level = CustomerLevel::create([
            'name' => 'Old Level',
            'discount_percentage' => 5,
            'default_credit_limit' => 10000
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->call('edit', $level->id)
            ->set('form.name', 'Updated Level Name')
            ->call('save');

        $this->assertDatabaseHas('customer_levels', [
            'id' => $level->id,
            'name' => 'Updated Level Name',
        ]);
    }

    public function test_super_admin_can_deactivate_activate_customer_level()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $level = CustomerLevel::create([
            'name' => 'Toggle Level',
            'discount_percentage' => 5,
            'default_credit_limit' => 10000,
            'is_active' => true,
            'gst_percentage' => 12.0,
            'hsn_code' => '6205',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->call('toggleStatus', $level->id);

        $this->assertDatabaseHas('customer_levels', [
            'id' => $level->id,
            'is_active' => false,
        ]);
    }

    public function test_super_admin_can_delete_customer_level()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $level = CustomerLevel::create([
            'name' => 'Level to Delete',
            'discount_percentage' => 5,
            'default_credit_limit' => 10000
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\CustomerLevels\CustomerLevelIndexPage::class)
            ->call('confirmDelete', $level->id)
            ->call('delete');

        $this->assertSoftDeleted('customer_levels', [
            'id' => $level->id,
        ]);
    }

    public function test_level_code_is_not_required_and_not_shown_in_ui()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/customer-levels');
        $response->assertDontSee('Level Code');
        $response->assertDontSee('form.level_code');
    }
}
