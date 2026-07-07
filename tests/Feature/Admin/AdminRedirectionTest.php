<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRedirectionTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
        
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'access dashboard', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'access orders', 'guard_name' => 'web']);
    }

    public function test_super_admin_redirects_to_dashboard(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertEquals(route('admin.dashboard'), $this->authService->getWebRedirectRoute($superAdmin));
        $this->assertEquals('/admin/dashboard', $this->authService->getRedirectPath($superAdmin));
    }

    public function test_admin_with_dashboard_access_redirects_to_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('access dashboard');

        $this->assertEquals(route('admin.dashboard'), $this->authService->getWebRedirectRoute($admin));
        $this->assertEquals('/admin/dashboard', $this->authService->getRedirectPath($admin));
    }

    public function test_admin_without_dashboard_but_with_orders_access_redirects_to_orders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('access orders');

        $this->assertEquals(route('admin.orders.index'), $this->authService->getWebRedirectRoute($admin));
        $this->assertEquals('/admin/orders', $this->authService->getRedirectPath($admin));
    }

    public function test_admin_without_dashboard_but_with_products_access_redirects_to_products(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $admin->givePermissionTo('access products');

        $this->assertEquals(route('admin.products.index'), $this->authService->getWebRedirectRoute($admin));
        $this->assertEquals('/admin/products', $this->authService->getRedirectPath($admin));
    }

    public function test_admin_with_no_permissions_redirects_to_home(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertEquals(route('home'), $this->authService->getWebRedirectRoute($admin));
        $this->assertEquals('/home', $this->authService->getRedirectPath($admin));
    }
}
