<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\CustomerLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Credit\CreditManagementPage;
use Spatie\Permission\Models\Role;

class CreditManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $user;
    private Customer $customer;
    private CustomerLevel $level;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole($customerRole);

        $this->level = CustomerLevel::create([
            'name' => 'Retailer Level 1',
            'discount_percentage' => 5,
            'default_credit_limit' => 100000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'customer_number' => 'KT-CUST-100',
            'customer_level_id' => $this->level->id,
            'company_name' => 'Raj Retailers',
            'gst_number' => '12ABCDE1234F1Z1',
            'contact_person' => 'Rajesh Kumar',
            'mobile_number' => $this->user->mobile_number ?: '9999999999',
            'email' => $this->user->email,
            'credit_limit' => 100000,
            'outstanding_amount' => 50000,
            'available_credit' => 50000,
            'overdue_amount' => 0,
            'allow_credit_beyond_limit' => false,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_credit_management_page()
    {
        $response = $this->get('/admin/credit-management');
        $response->assertRedirect('/login');
    }

    public function test_normal_customer_cannot_access_credit_management_page()
    {
        $response = $this->actingAs($this->user)->get('/admin/credit-management');
        $response->assertRedirect(route('home'));
    }

    public function test_super_admin_can_access_credit_management_page_and_see_real_metrics()
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/credit-management');
        $response->assertSuccessful();
        $response->assertSee('Raj Retailers');
        $response->assertSee('100,000');
    }

    public function test_super_admin_can_update_customer_credit_limit()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('openLimitModal', $this->customer->id)
            ->set('limitForm.credit_limit', 150000)
            ->set('limitForm.note', 'Increased credit limit on request')
            ->call('saveLimit');

        $this->assertEquals(150000, (float) $this->customer->fresh()->credit_limit);
        $this->assertEquals(100000, (float) $this->customer->fresh()->available_credit);
        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'credit_limit_change',
            'credit_limit_after' => 150000,
        ]);
    }

    public function test_super_admin_can_record_payment()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('openPaymentModal', $this->customer->id)
            ->set('paymentForm.amount', 20000)
            ->set('paymentForm.note', 'Part payment via bank transfer')
            ->call('savePayment');

        $this->assertEquals(30000, (float) $this->customer->fresh()->outstanding_amount);
        $this->assertEquals(70000, (float) $this->customer->fresh()->available_credit);
        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'payment_received',
            'amount' => 20000,
        ]);
    }

    public function test_super_admin_cannot_record_payment_exceeding_outstanding()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('openPaymentModal', $this->customer->id)
            ->set('paymentForm.amount', 60000)
            ->set('paymentForm.note', 'Overpayment')
            ->call('savePayment')
            ->assertHasErrors(['paymentForm.amount']);

        $this->assertEquals(50000, (float) $this->customer->fresh()->outstanding_amount);
    }

    public function test_super_admin_can_apply_and_release_credit_hold()
    {
        // Apply hold
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('openHoldModal', $this->customer->id)
            ->set('holdForm.reason', 'Delayed payment for invoices')
            ->call('saveHold');

        $this->assertTrue((bool) $this->customer->fresh()->credit_hold);
        $this->assertEquals('Delayed payment for invoices', $this->customer->fresh()->credit_hold_reason);

        // Release hold
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('openReleaseModal', $this->customer->id)
            ->set('releaseForm.note', 'Cleared dues')
            ->call('saveRelease');

        $this->assertFalse((bool) $this->customer->fresh()->credit_hold);
        $this->assertNull($this->customer->fresh()->credit_hold_reason);
    }

    public function test_super_admin_can_toggle_beyond_limit_privilege()
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('toggleBeyondLimit', $this->customer->id);

        $this->assertTrue((bool) $this->customer->fresh()->allow_credit_beyond_limit);

        Livewire::actingAs($this->superAdmin)
            ->test(CreditManagementPage::class)
            ->call('toggleBeyondLimit', $this->customer->id);

        $this->assertFalse((bool) $this->customer->fresh()->allow_credit_beyond_limit);
    }
}
