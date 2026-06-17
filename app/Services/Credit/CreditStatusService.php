<?php

namespace App\Services\Credit;

use App\Models\Customer;

class CreditStatusService
{
    /**
     * Get the current status info for a customer.
     */
    public function getStatus(Customer $customer): array
    {
        if ($customer->credit_hold) {
            return [
                'value' => 'on_hold',
                'label' => 'On Hold',
                'badge' => 'danger', // maps to red/error styling
                'message' => 'Credit account is on hold: ' . ($customer->credit_hold_reason ?: 'No reason specified'),
            ];
        }

        $limit = (float) $customer->credit_limit;
        $outstanding = (float) $customer->outstanding_amount;

        if ($limit <= 0) {
            return [
                'value' => 'no_credit',
                'label' => 'No Credit Limit',
                'badge' => 'secondary',
                'message' => 'This customer has no credit facility set.',
            ];
        }

        if ($outstanding > $limit) {
            return [
                'value' => 'over_limit',
                'label' => 'Over Limit',
                'badge' => 'danger',
                'message' => 'Outstanding balance exceeds credit limit.',
            ];
        }

        if ($outstanding >= $limit * 0.85) {
            return [
                'value' => 'near_limit',
                'label' => 'Near Limit',
                'badge' => 'warning',
                'message' => 'Credit utilization is above 85%.',
            ];
        }

        return [
            'value' => 'healthy',
            'label' => 'Healthy',
            'badge' => 'success',
            'message' => 'Credit account in good standing.',
        ];
    }

    /**
     * Get risk rating based on outstanding and limits.
     */
    public function getRiskLevel(Customer $customer): array
    {
        if ($customer->credit_hold) {
            return ['level' => 'High', 'color' => 'text-error'];
        }

        $limit = (float) $customer->credit_limit;
        $outstanding = (float) $customer->outstanding_amount;
        $overdue = (float) $customer->overdue_amount;

        if ($overdue > 0) {
            return ['level' => 'High', 'color' => 'text-error'];
        }

        if ($limit <= 0) {
            return ['level' => 'None', 'color' => 'text-on-surface-variant'];
        }

        $ratio = $outstanding / $limit;

        if ($ratio >= 1.0) {
            return ['level' => 'High', 'color' => 'text-error'];
        }

        if ($ratio >= 0.7) {
            return ['level' => 'Medium', 'color' => 'text-warning'];
        }

        return ['level' => 'Low', 'color' => 'text-[#0F8A46]'];
    }

    /**
     * Get available credit actions for this customer.
     */
    public function getAvailableActions(Customer $customer): array
    {
        return [
            'can_update_limit' => true,
            'can_record_payment' => (float) $customer->outstanding_amount > 0,
            'can_adjust_outstanding' => true,
            'can_toggle_beyond_limit' => true,
            'can_place_on_hold' => !$customer->credit_hold,
            'can_release_hold' => (bool) $customer->credit_hold,
        ];
    }
}
