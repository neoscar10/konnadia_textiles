<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AdminRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_guest_cannot_view_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_super_admin_visiting_login_goes_to_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_authenticated_normal_user_visiting_login_goes_to_home(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/portal/dashboard');
    }

    public function test_authenticated_normal_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertRedirect('/home');
        $response->assertSessionHas('error', 'You do not have administrative access.');
    }
}
