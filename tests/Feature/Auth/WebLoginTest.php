<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class WebLoginTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::create(['name' => 'super_admin']);
        Role::create(['name' => 'customer']);
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_admin_login_redirects_to_login(): void
    {
        $response = $this->get('/admin/login');
        $response->assertRedirect('/login');
    }

    public function test_super_admin_can_log_in_and_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('super_admin');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_normal_user_logs_in_and_is_redirected_to_home(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $user->assignRole('customer');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/portal/dashboard');
    }
}
