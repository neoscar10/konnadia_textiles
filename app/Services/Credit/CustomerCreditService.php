<?php

namespace App\Services\Credit;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerActivityLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomerCreditService
{
    protected CustomerActivityLogService $activityLogService;

    public function __construct(CustomerActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

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

            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->outstanding_amount = $outstandingBefore + $orderTotal;
            $customer->available_credit = $limitBefore - $customer->outstanding_amount;
            $customer->save();

            $order->update(['credit_applied_at' => now()]);

            CreditLedgerService::record($customer, [
                'type' => 'order_credit',
                'direction' => 'debit',
                'amount' => $orderTotal,
                'order_id' => $order->id,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $customer->outstanding_amount,
                'available_before' => $availableBefore,
                'available_after' => $customer->available_credit,
                'note' => "Credit utilized for Order #{$order->order_number}",
            ]);
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

            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->outstanding_amount = max(0.00, $outstandingBefore - $orderTotal);
            $customer->available_credit = $limitBefore - $customer->outstanding_amount;
            $customer->save();

            $order->update(['credit_reversed_at' => now()]);

            CreditLedgerService::record($customer, [
                'type' => 'reversal',
                'direction' => 'credit',
                'amount' => $orderTotal,
                'order_id' => $order->id,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $customer->outstanding_amount,
                'available_before' => $availableBefore,
                'available_after' => $customer->available_credit,
                'note' => "Credit reversed for Order #{$order->order_number}",
            ]);
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

    /**
     * Record a payment received from customer.
     */
    public function recordPayment(Customer $customer, float $amount, User $admin, ?string $note = null): Customer
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }

        if ($amount > (float) $customer->outstanding_amount) {
            throw new InvalidArgumentException("Payment amount cannot exceed the outstanding balance of ₹" . number_format($customer->outstanding_amount, 2));
        }

        DB::transaction(function () use ($customer, $amount, $admin, $note) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->outstanding_amount = max(0.00, $outstandingBefore - $amount);
            $customer->available_credit = $limitBefore - $customer->outstanding_amount;
            $customer->save();

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => 'payment_received',
                'direction' => 'credit',
                'amount' => $amount,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $customer->outstanding_amount,
                'available_before' => $availableBefore,
                'available_after' => $customer->available_credit,
                'note' => $note ?? "Payment received",
            ]);

            $this->activityLogService->record($customer, 'payment_recorded', [
                'actor_user_id' => $admin->id,
                'title' => 'Payment Recorded',
                'description' => "Payment of ₹" . number_format($amount, 2) . " recorded by {$admin->name}." . ($note ? " Note: {$note}" : ''),
                'metadata' => ['amount' => $amount, 'note' => $note],
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Adjust outstanding amount (increase or decrease).
     */
    public function adjustOutstanding(Customer $customer, float $amount, string $direction, User $admin, ?string $note = null): Customer
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Adjustment amount must be greater than zero.");
        }

        if (!in_array($direction, ['increase', 'decrease'])) {
            throw new InvalidArgumentException("Direction must be 'increase' or 'decrease'.");
        }

        DB::transaction(function () use ($customer, $amount, $direction, $admin, $note) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            if ($direction === 'increase') {
                $customer->outstanding_amount = $outstandingBefore + $amount;
                $ledgerType = 'adjustment_increase';
                $ledgerDir = 'debit';
            } else {
                $customer->outstanding_amount = max(0.00, $outstandingBefore - $amount);
                $ledgerType = 'adjustment_decrease';
                $ledgerDir = 'credit';
            }

            $customer->available_credit = $limitBefore - $customer->outstanding_amount;
            $customer->save();

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => $ledgerType,
                'direction' => $ledgerDir,
                'amount' => $amount,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $customer->outstanding_amount,
                'available_before' => $availableBefore,
                'available_after' => $customer->available_credit,
                'note' => $note ?? "Credit adjustment ({$direction})",
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Update customer credit limit.
     */
    public function updateCreditLimit(Customer $customer, float $newLimit, User $admin, ?string $note = null): Customer
    {
        if ($newLimit < 0) {
            throw new InvalidArgumentException("Credit limit cannot be negative.");
        }

        DB::transaction(function () use ($customer, $newLimit, $admin, $note) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->credit_limit = $newLimit;
            $customer->available_credit = $newLimit - $outstandingBefore;
            $customer->last_credit_review_at = now();
            $customer->save();

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => 'credit_limit_change',
                'direction' => 'neutral',
                'amount' => abs($newLimit - $limitBefore),
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $newLimit,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $outstandingBefore,
                'available_before' => $availableBefore,
                'available_after' => $customer->available_credit,
                'note' => $note ?? "Credit limit updated",
            ]);

            $this->activityLogService->record($customer, 'credit_limit_updated', [
                'actor_user_id' => $admin->id,
                'title' => 'Credit Limit Updated',
                'description' => "Credit limit changed from ₹" . number_format($limitBefore, 2) . " to ₹" . number_format($newLimit, 2) . " by {$admin->name}.",
                'metadata' => ['limit_before' => $limitBefore, 'limit_after' => $newLimit, 'note' => $note],
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Place customer credit on hold.
     */
    public function setCreditHold(Customer $customer, User $admin, string $reason): Customer
    {
        DB::transaction(function () use ($customer, $admin, $reason) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->credit_hold = true;
            $customer->credit_hold_reason = $reason;
            $customer->credit_hold_at = now();
            $customer->credit_hold_by = $admin->id;
            $customer->save();

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => 'credit_hold',
                'direction' => 'neutral',
                'amount' => 0.00,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $outstandingBefore,
                'available_before' => $availableBefore,
                'available_after' => $availableBefore,
                'note' => "Credit hold applied: {$reason}",
            ]);

            $this->activityLogService->record($customer, 'credit_hold_applied', [
                'actor_user_id' => $admin->id,
                'title' => 'Credit Hold Applied',
                'description' => "Credit account placed on hold by {$admin->name}. Reason: {$reason}.",
                'metadata' => ['reason' => $reason],
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Release credit hold from customer.
     */
    public function releaseCreditHold(Customer $customer, User $admin, ?string $note = null): Customer
    {
        DB::transaction(function () use ($customer, $admin, $note) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->credit_hold = false;
            $customer->credit_hold_reason = null;
            $customer->credit_hold_at = null;
            $customer->credit_hold_by = null;
            $customer->save();

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => 'credit_release',
                'direction' => 'neutral',
                'amount' => 0.00,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $outstandingBefore,
                'available_before' => $availableBefore,
                'available_after' => $availableBefore,
                'note' => $note ?? "Credit hold released",
            ]);

            $this->activityLogService->record($customer, 'credit_hold_released', [
                'actor_user_id' => $admin->id,
                'title' => 'Credit Hold Released',
                'description' => "Credit hold released by {$admin->name}." . ($note ? " Note: {$note}" : ''),
                'metadata' => ['note' => $note],
            ]);
        });

        return $customer->fresh();
    }

    /**
     * Toggle customer's privilege to buy beyond credit limit.
     */
    public function toggleCreditBeyondLimit(Customer $customer, bool $allowed, User $admin, ?string $note = null): Customer
    {
        DB::transaction(function () use ($customer, $allowed, $admin, $note) {
            $limitBefore = (float) $customer->credit_limit;
            $outstandingBefore = (float) $customer->outstanding_amount;
            $availableBefore = (float) $customer->available_credit;

            $customer->allow_credit_beyond_limit = $allowed;
            $customer->save();

            $statusText = $allowed ? 'Allowed' : 'Not Allowed';

            CreditLedgerService::record($customer, [
                'user_id' => $admin->id,
                'type' => 'credit_privilege_changed',
                'direction' => 'neutral',
                'amount' => 0.00,
                'credit_limit_before' => $limitBefore,
                'credit_limit_after' => $limitBefore,
                'outstanding_before' => $outstandingBefore,
                'outstanding_after' => $outstandingBefore,
                'available_before' => $availableBefore,
                'available_after' => $availableBefore,
                'note' => $note ?? "Purchasing beyond limit set to: {$statusText}",
                'metadata' => ['allow_credit_beyond_limit' => $allowed],
            ]);

            $event = $allowed ? 'credit_privilege_enabled' : 'credit_privilege_disabled';
            $this->activityLogService->record($customer, $event, [
                'actor_user_id' => $admin->id,
                'title' => 'Credit Beyond Limit ' . ($allowed ? 'Enabled' : 'Disabled'),
                'description' => "Purchasing beyond credit limit set to '{$statusText}' by {$admin->name}.",
                'metadata' => ['allow_credit_beyond_limit' => $allowed, 'note' => $note],
            ]);
        });

        return $customer->fresh();
    }
}
