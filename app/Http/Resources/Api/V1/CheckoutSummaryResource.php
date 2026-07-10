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

        $checkoutMethods = [
            [
                'value' => 'manual_payment',
                'label' => 'Manual Payment',
                'enabled' => true,
                'description' => 'Upload a copy of your bank transfer or deposit slip.',
            ],
            [
                'value' => 'credit',
                'label' => 'Credit Purchase',
                'enabled' => (bool) $eligibility['can_use_credit'],
                'description' => $eligibility['message'],
            ]
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
                'available_credit' => (float) $customer->available_credit,
                'outstanding_amount' => (float) $customer->outstanding_amount,
                'allow_credit_beyond_limit' => (bool) $customer->allow_credit_beyond_limit,
            ],
            'credit_eligibility' => [
                'can_use_credit' => (bool) $eligibility['can_use_credit'],
                'message' => $eligibility['message'],
                'is_within_limit' => (bool) $eligibility['is_within_limit'],
                'is_privileged_override' => (bool) $eligibility['is_privileged_override'],
            ],
            'checkout_methods' => $checkoutMethods
        ];
    }
}
