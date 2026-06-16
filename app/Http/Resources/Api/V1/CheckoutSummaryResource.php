<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cartData = $this->resource['cart'];
        $customer = $request->user()->customer;
        $eligibility = $this->resource['credit_eligibility'];

        $creditMethod = [
            'value' => 'credit',
            'label' => 'Use Available Credit',
            'enabled' => (bool) $eligibility['can_use_credit'],
            'description' => $eligibility['message'],
        ];

        if ($eligibility['is_privileged_override']) {
            $creditMethod['badge'] = 'Credit Override Allowed';
        }

        $checkoutMethods = [
            [
                'value' => 'manual_payment',
                'label' => 'Manual Payment with Receipt',
                'enabled' => true,
                'description' => 'Upload proof of payment for admin verification.',
            ],
            $creditMethod
        ];

        return [
            'cart' => [
                'items_count' => count($cartData['items']),
                'items' => $cartData['items'],
                'summary' => [
                    'currency' => 'INR',
                    'subtotal' => (float) $cartData['totals']['subtotal'],
                    'gst_amount' => (float) $cartData['totals']['gst_amount'],
                    'total' => (float) $cartData['totals']['total'],
                    'formatted_total' => '₹' . number_format($cartData['totals']['total'], 2),
                ]
            ],
            'customer_credit' => [
                'credit_limit' => (float) $customer->credit_limit,
                'outstanding_amount' => (float) $customer->outstanding_amount,
                'available_credit' => (float) $customer->available_credit,
                'overdue_amount' => (float) ($customer->overdue_amount ?? 0.0),
                'allow_credit_beyond_limit' => (bool) $customer->allow_credit_beyond_limit,
            ],
            'credit_eligibility' => [
                'can_use_credit' => (bool) $eligibility['can_use_credit'],
                'is_within_limit' => (bool) $eligibility['is_within_limit'],
                'is_privileged_override' => (bool) $eligibility['is_privileged_override'],
                'credit_limit' => (float) $eligibility['credit_limit'],
                'available_credit' => (float) $eligibility['available_credit'],
                'order_total' => (float) $eligibility['order_total'],
                'excess_amount' => (float) $eligibility['excess_amount'],
                'message' => $eligibility['message'],
            ],
            'checkout_methods' => $checkoutMethods
        ];
    }
}
