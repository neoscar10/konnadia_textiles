<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use App\Models\HomeContentSection;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminSettingsAndContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $restrictedAdmin;
    protected string $superAdminToken;
    protected string $restrictedAdminToken;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $permHome = Permission::firstOrCreate(['name' => 'access home-content', 'guard_name' => 'web']);
        $permAdmins = Permission::firstOrCreate(['name' => 'access admins', 'guard_name' => 'web']);
        $permContact = Permission::firstOrCreate(['name' => 'access contact-messages', 'guard_name' => 'web']);

        $superRole->givePermissionTo([$permHome, $permAdmins, $permContact]);

        $this->superAdmin = User::factory()->create([
            'email' => 'super_config@konnadia.com',
            'password' => bcrypt('password123'),
        ]);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->restrictedAdmin = User::factory()->create([
            'email' => 'restricted_config@konnadia.com',
            'password' => bcrypt('password123'),
        ]);
        $this->restrictedAdmin->assignRole('admin');
        $this->restrictedAdminToken = JWTAuth::fromUser($this->restrictedAdmin);
    }

    /* -------------------------------------------------------------------------- */
    /* HOME CONTENT MANAGEMENT TESTS                                              */
    /* -------------------------------------------------------------------------- */

    public function test_guest_cannot_access_home_content_apis(): void
    {
        $response = $this->getJson('/api/v1/admin/home-content/stats');
        $response->assertStatus(401);
    }

    public function test_super_admin_can_manage_home_content_sections(): void
    {
        // 1. Stats & Options
        $statsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/home-content/stats');
        $statsResp->assertStatus(200)->assertJsonPath('success', true);

        $optionsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/home-content/options');
        $optionsResp->assertStatus(200)->assertJsonPath('success', true);

        // 2. Create Section
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/home-content', [
                'type' => 'banner',
                'title' => 'Festive Collection 2026',
                'subtitle' => 'Exclusive Indian Sarees',
                'is_active' => true,
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Festive Collection 2026');

        $sectionId = $createResp->json('data.id');

        // 3. Show Section
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/home-content/' . $sectionId);
        $showResp->assertStatus(200)->assertJsonPath('success', true);

        // 4. Toggle Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/admin/home-content/' . $sectionId . '/toggle-status');
        $toggleResp->assertStatus(200)->assertJsonPath('data.is_active', false);

        // 5. Delete Section
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/home-content/' . $sectionId);
        $deleteResp->assertStatus(200)->assertJsonPath('success', true);
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN USERS MANAGEMENT TESTS                                               */
    /* -------------------------------------------------------------------------- */

    public function test_super_admin_can_manage_admin_users(): void
    {
        // 1. Permissions List
        $permResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/admins/permissions-list');
        $permResp->assertStatus(200)->assertJsonPath('success', true);

        // 2. Create Admin User
        $createResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/admins', [
                'name' => 'Manager Staff',
                'email' => 'manager_staff@konnadia.com',
                'mobile_number' => '+91 9998887776',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'is_active' => true,
                'permissions' => ['access products', 'access orders'],
            ]);

        $createResp->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'manager_staff@konnadia.com');

        $adminId = $createResp->json('data.id');

        // 3. List Admins
        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/admins?search=Manager');
        $listResp->assertStatus(200)->assertJsonCount(1, 'data');

        // 4. Update Admin User
        $updateResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->putJson('/api/v1/admin/admins/' . $adminId, [
                'name' => 'Senior Manager Staff',
                'email' => 'manager_staff@konnadia.com',
                'mobile_number' => '+91 9998887776',
                'is_active' => true,
                'permissions' => ['access products', 'access orders', 'access inventory'],
            ]);
        $updateResp->assertStatus(200)->assertJsonPath('data.name', 'Senior Manager Staff');

        // 5. Toggle Admin Status
        $toggleResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/admin/admins/' . $adminId . '/toggle-status');
        $toggleResp->assertStatus(200)->assertJsonPath('data.is_active', false);

        // 6. Delete Admin User
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/admins/' . $adminId);
        $deleteResp->assertStatus(200)->assertJsonPath('success', true);
    }

    /* -------------------------------------------------------------------------- */
    /* SETTINGS & PASSWORD TESTS                                                  */
    /* -------------------------------------------------------------------------- */

    public function test_admin_can_fetch_settings_profile_and_change_password(): void
    {
        $profileResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/settings');
        $profileResp->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'super_config@konnadia.com');

        $changePassResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->postJson('/api/v1/admin/settings/change-password', [
                'current_password' => 'password123',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $changePassResp->assertStatus(200)->assertJsonPath('success', true);
    }

    /* -------------------------------------------------------------------------- */
    /* CONTACT MESSAGES TESTS                                                     */
    /* -------------------------------------------------------------------------- */

    public function test_super_admin_can_manage_contact_messages(): void
    {
        $msg = ContactMessage::create([
            'name' => 'Aarav Mehta',
            'email' => 'aarav@gmail.com',
            'phone' => '+91 9112233445',
            'subject' => 'Wholesale Inquiry',
            'message' => 'Interested in bulk orders of silk sarees.',
            'is_read' => false,
        ]);

        // 1. Stats
        $statsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/contact-messages/stats');
        $statsResp->assertStatus(200)->assertJsonPath('data.unread_messages', 1);

        // 2. Listing
        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/contact-messages?status=unread');
        $listResp->assertStatus(200)->assertJsonCount(1, 'data');

        // 3. Show Detail (Auto marks read)
        $showResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/contact-messages/' . $msg->id);
        $showResp->assertStatus(200)->assertJsonPath('data.is_read', true);

        // 4. Mark Unread
        $unreadResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->patchJson('/api/v1/admin/contact-messages/' . $msg->id . '/mark-unread');
        $unreadResp->assertStatus(200)->assertJsonPath('data.is_read', false);

        // 5. Delete Message
        $deleteResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->deleteJson('/api/v1/admin/contact-messages/' . $msg->id);
        $deleteResp->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_guest_or_user_can_submit_contact_form_message(): void
    {
        $response = $this->postJson('/api/v1/contact', [
            'name' => 'Karan Sharma',
            'email' => 'karan@gmail.com',
            'phone' => '+91 9876543210',
            'subject' => 'Bulk Purchase Query',
            'message' => 'We would like to request catalog prices for bulk orders.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'karan@gmail.com',
            'subject' => 'Bulk Purchase Query',
        ]);
    }

    /* -------------------------------------------------------------------------- */
    /* DASHBOARD, UNITS, CREDIT, & REPORTS TESTS                                  */
    /* -------------------------------------------------------------------------- */

    public function test_super_admin_can_fetch_dashboard_analytics(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/dashboard?date_range=30_days');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metadata.currency', 'INR');
    }

    public function test_super_admin_can_fetch_units_and_reports(): void
    {
        $unitsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/units');
        $unitsResp->assertStatus(200)->assertJsonPath('success', true);

        $salesResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/reports/sales');
        $salesResp->assertStatus(200)->assertJsonPath('success', true);

        $custReportResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/reports/customers');
        $custReportResp->assertStatus(200)->assertJsonPath('success', true);

        $invReportResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/reports/inventory');
        $invReportResp->assertStatus(200)->assertJsonPath('success', true);
    }

    public function test_super_admin_can_manage_credit_accounts(): void
    {
        $statsResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/credit-management/stats');
        $statsResp->assertStatus(200)->assertJsonPath('success', true);

        $listResp = $this->withHeader('Authorization', 'Bearer ' . $this->superAdminToken)
            ->getJson('/api/v1/admin/credit-management');
        $listResp->assertStatus(200)->assertJsonPath('success', true);
    }
}
