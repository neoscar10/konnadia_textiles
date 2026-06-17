<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Customer;
use App\Services\Checkout\CreditEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreditEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CreditEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CreditEligibilityService();
    }

    public function test_zero_credit_limit_returns_cannot_use_credit(): void
    {
        $customer = new Customer([
            'credit_limit' => 0.0,
            'available_credit' => 0.0,
            'outstanding_amount' => 0.0,
            'allow_credit_beyond_limit' => false,
        ]);

        $result = $this->service->evaluate($customer, 100.0);

        $this->assertFalse($result['can_use_credit']);
        $this->assertFalse($result['is_within_limit']);
        $this->assertFalse($result['is_privileged_override']);
        $this->assertEquals('Your account does not have a credit facility. Please use manual payment with receipt upload.', $result['message']);
    }

    public function test_within_limit_evaluation_returns_correct_structure(): void
    {
        $customer = new Customer([
            'credit_limit' => 1000.0,
            'available_credit' => 800.0,
            'outstanding_amount' => 200.0,
            'allow_credit_beyond_limit' => false,
        ]);

        $result = $this->service->evaluate($customer, 500.0);

        $this->assertTrue($result['can_use_credit']);
        $this->assertTrue($result['is_within_limit']);
        $this->assertFalse($result['is_privileged_override']);
        $this->assertEquals(0.0, $result['excess_amount']);
        $this->assertEquals('This order is within your available credit limit.', $result['message']);
    }

    public function test_over_limit_without_privilege_blocks_credit(): void
    {
        $customer = new Customer([
            'credit_limit' => 1000.0,
            'available_credit' => 300.0,
            'outstanding_amount' => 700.0,
            'allow_credit_beyond_limit' => false,
        ]);

        $result = $this->service->evaluate($customer, 500.0);

        $this->assertFalse($result['can_use_credit']);
        $this->assertFalse($result['is_within_limit']);
        $this->assertFalse($result['is_privileged_override']);
        $this->assertEquals(200.0, $result['excess_amount']);
        $this->assertEquals('This order exceeds your available credit limit. Please reduce your cart value or choose manual payment with receipt upload.', $result['message']);
    }

    public function test_over_limit_with_privilege_allows_credit(): void
    {
        $customer = new Customer([
            'credit_limit' => 1000.0,
            'available_credit' => 300.0,
            'outstanding_amount' => 700.0,
            'allow_credit_beyond_limit' => true,
        ]);

        $result = $this->service->evaluate($customer, 500.0);

        $this->assertTrue($result['can_use_credit']);
        $this->assertFalse($result['is_within_limit']);
        $this->assertTrue($result['is_privileged_override']);
        $this->assertEquals(200.0, $result['excess_amount']);
        $this->assertEquals('This order exceeds your available credit limit, but your account is allowed to purchase beyond the limit.', $result['message']);
    }

    public function test_credit_hold_blocks_checkout(): void
    {
        $customer = new Customer([
            'credit_limit' => 1000.0,
            'available_credit' => 800.0,
            'outstanding_amount' => 200.0,
            'allow_credit_beyond_limit' => true,
            'credit_hold' => true,
            'credit_hold_reason' => 'Late payment',
        ]);

        $result = $this->service->evaluate($customer, 100.0);

        $this->assertFalse($result['can_use_credit']);
        $this->assertFalse($result['is_within_limit']);
        $this->assertFalse($result['is_privileged_override']);
        $this->assertEquals('Your credit facility is currently on hold. Please contact administration or use manual payment.', $result['message']);
    }
}
