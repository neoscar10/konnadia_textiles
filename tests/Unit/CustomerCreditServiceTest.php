<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\User;
use App\Services\Credit\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

class CustomerCreditServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerCreditService $service;
    private Customer $customer;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CustomerCreditService();
        $this->admin = User::factory()->create();

        $this->customer = Customer::create([
            'customer_number' => 'KT-C-TEST',
            'company_name' => 'Test Corp',
            'gst_number' => '12ABCDE9999F1Z1',
            'contact_person' => 'Test Contact',
            'mobile_number' => '9999999900',
            'email' => 'test@corp.com',
            'credit_limit' => 500000,
            'outstanding_amount' => 100000,
            'available_credit' => 400000,
            'overdue_amount' => 0,
            'allow_credit_beyond_limit' => false,
            'is_active' => true,
        ]);
    }

    public function test_record_payment_deducts_outstanding_and_logs_ledger()
    {
        $this->service->recordPayment($this->customer, 40000, $this->admin, 'Paid cash');

        $this->customer->refresh();
        $this->assertEquals(60000, (float) $this->customer->outstanding_amount);
        $this->assertEquals(440000, (float) $this->customer->available_credit);

        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'payment_received',
            'direction' => 'credit',
            'amount' => 40000,
            'outstanding_before' => 100000,
            'outstanding_after' => 60000,
            'note' => 'Paid cash',
        ]);
    }

    public function test_record_payment_throws_exception_if_exceeds_outstanding()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->recordPayment($this->customer, 120000, $this->admin, 'Excess payment');
    }

    public function test_adjust_outstanding_increase()
    {
        $this->service->adjustOutstanding($this->customer, 25000, 'increase', $this->admin, 'Extra shipping charge');

        $this->customer->refresh();
        $this->assertEquals(125000, (float) $this->customer->outstanding_amount);
        $this->assertEquals(375000, (float) $this->customer->available_credit);

        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'adjustment_increase',
            'direction' => 'debit',
            'amount' => 25000,
            'outstanding_after' => 125000,
        ]);
    }

    public function test_adjust_outstanding_decrease()
    {
        $this->service->adjustOutstanding($this->customer, 20000, 'decrease', $this->admin, 'Damaged items credit note');

        $this->customer->refresh();
        $this->assertEquals(80000, (float) $this->customer->outstanding_amount);
        $this->assertEquals(420000, (float) $this->customer->available_credit);

        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'adjustment_decrease',
            'direction' => 'credit',
            'amount' => 20000,
            'outstanding_after' => 80000,
        ]);
    }

    public function test_update_credit_limit()
    {
        $this->service->updateCreditLimit($this->customer, 800000, $this->admin, 'Increase limit');

        $this->customer->refresh();
        $this->assertEquals(800000, (float) $this->customer->credit_limit);
        $this->assertEquals(700000, (float) $this->customer->available_credit);

        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'credit_limit_change',
            'credit_limit_after' => 800000,
        ]);
    }

    public function test_toggle_credit_beyond_limit()
    {
        $this->service->toggleCreditBeyondLimit($this->customer, true, $this->admin, 'Allow extra');

        $this->customer->refresh();
        $this->assertTrue((bool) $this->customer->allow_credit_beyond_limit);

        $this->assertDatabaseHas('customer_credit_ledgers', [
            'customer_id' => $this->customer->id,
            'type' => 'credit_privilege_changed',
        ]);
    }
}
