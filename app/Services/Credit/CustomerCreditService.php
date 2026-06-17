<?php

namespace App\Services\Credit;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CustomerCreditService
{
    /**
     * Apply a credit order to the customer's balance.
     * Increases outstanding_amount and recalculates available_credit.
     */
    public function applyCreditOrder(Customer $customer, Order $order): void
    {
        if ($order->credit_applied_at !== null) {
            return; // Already applied
        }

        DB::transaction(function () use ($customer, $order) {
            $orderTotal = (float) $order->total_amount;

            $customer->outstanding_amount = (float) $customer->outstanding_amount + $orderTotal;
            $customer->available_credit = (float) $customer->credit_limit - (float) $customer->outstanding_amount;
            $customer->save();

            $order->update(['credit_applied_at' => now()]);
        });
    }

    /**
     * Reverse a credit order from the customer's balance (e.g. on rejection/cancellation).
     * Decreases outstanding_amount and recalculates available_credit.
     */
    public function reverseCreditOrder(Customer $customer, Order $order): void
    {
        if ($order->credit_applied_at === null) {
            return; // Credit was never applied, nothing to reverse
        }

        if ($order->credit_reversed_at !== null) {
            return; // Already reversed
        }

        DB::transaction(function () use ($customer, $order) {
            $orderTotal = (float) $order->total_amount;

            $customer->outstanding_amount = (float) $customer->outstanding_amount - $orderTotal;
            $customer->available_credit = (float) $customer->credit_limit - (float) $customer->outstanding_amount;
            $customer->save();

            $order->update(['credit_reversed_at' => now()]);
        });
    }

    /**
     * Recalculate available_credit from credit_limit and outstanding_amount.
     * available_credit can go negative if customer has purchased beyond limit.
     */
    public function recalculateAvailableCredit(Customer $customer): Customer
    {
        DB::transaction(function () use ($customer) {
            $customer->available_credit = (float) $customer->credit_limit - (float) $customer->outstanding_amount;
            $customer->save();
        });

        return $customer->fresh();
    }
}
