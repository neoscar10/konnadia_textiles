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
                'value' => 'regular',
                'label' => 'Standard Checkout',
                'enabled' => true,
                'description' => 'Place your order and proceed to admin review.',
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
            'checkout_methods' => $checkoutMethods
        ];
    }
}
