<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pricingService = app(\App\Services\Cart\CartPricingService::class);
        $totals = $pricingService->recalculateCart($this->resource);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'items_count' => (int) $this->items()->count(),
            'items' => CartItemResource::collection($this->items),
            'summary' => [
                'currency' => 'INR',
                'subtotal' => (float) $totals['subtotal'],
                'gst_amount' => (float) $totals['gst_amount'],
                'total' => (float) $totals['total'],
                'formatted_subtotal' => '₹' . number_format($totals['subtotal'], 2),
                'formatted_gst_amount' => '₹' . number_format($totals['gst_amount'], 2),
                'formatted_total' => '₹' . number_format($totals['total'], 2),
            ]
        ];
    }
}
