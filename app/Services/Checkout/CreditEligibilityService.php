<?php

namespace App\Services\Checkout;

use App\Models\Customer;

class CreditEligibilityService
{
    /**
     * Evaluate whether a customer can use credit for an order of the given total.
     */
    public function evaluate(Customer $customer, float $orderTotal): array
    {
        $creditLimit = (float) $customer->credit_limit;
        $availableCredit = (float) $customer->available_credit;
        $outstandingAmount = (float) $customer->outstanding_amount;
        $allowBeyondLimit = (bool) $customer->allow_credit_beyond_limit;

        if ($customer->credit_hold) {
            return [
                'can_use_credit' => false,
                'is_within_limit' => false,
                'is_privileged_override' => false,
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
                'outstanding_amount' => $outstandingAmount,
                'order_total' => $orderTotal,
                'excess_amount' => $orderTotal,
                'message' => 'Your credit facility is currently on hold. Please contact administration or use manual payment.',
            ];
        }

        // If credit limit is zero, customer has no credit facility
        if ($creditLimit <= 0) {
            return [
                'can_use_credit' => false,
                'is_within_limit' => false,
                'is_privileged_override' => false,
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
                'outstanding_amount' => $outstandingAmount,
                'order_total' => $orderTotal,
                'excess_amount' => $orderTotal,
                'message' => 'Your account does not have a credit facility. Please use manual payment with receipt upload.',
            ];
        }

        $isWithinLimit = $orderTotal <= $availableCredit;
        $excessAmount = $isWithinLimit ? 0.0 : round($orderTotal - $availableCredit, 2);

        if ($isWithinLimit) {
            return [
                'can_use_credit' => true,
                'is_within_limit' => true,
                'is_privileged_override' => false,
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
                'outstanding_amount' => $outstandingAmount,
                'order_total' => $orderTotal,
                'excess_amount' => 0.0,
                'message' => 'This order is within your available credit limit.',
            ];
        }

        // Order exceeds available credit
        if ($allowBeyondLimit) {
            return [
                'can_use_credit' => true,
                'is_within_limit' => false,
                'is_privileged_override' => true,
                'credit_limit' => $creditLimit,
                'available_credit' => $availableCredit,
                'outstanding_amount' => $outstandingAmount,
                'order_total' => $orderTotal,
                'excess_amount' => $excessAmount,
                'message' => 'This order exceeds your available credit limit, but your account is allowed to purchase beyond the limit.',
            ];
        }

        return [
            'can_use_credit' => false,
            'is_within_limit' => false,
            'is_privileged_override' => false,
            'credit_limit' => $creditLimit,
            'available_credit' => $availableCredit,
            'outstanding_amount' => $outstandingAmount,
            'order_total' => $orderTotal,
            'excess_amount' => $excessAmount,
            'message' => 'This order exceeds your available credit limit. Please reduce your cart value or choose manual payment with receipt upload.',
        ];
    }
}
