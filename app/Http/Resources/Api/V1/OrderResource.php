<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'checkout_method' => $this->checkout_method,
            'checkout_method_label' => $this->checkout_method_label,
            'payment_status' => $this->payment_status,
            'credit_status' => $this->credit_status,
            'totals' => [
                'currency' => 'INR',
                'subtotal' => (float) $this->subtotal,
                'gst_amount' => (float) $this->gst_amount,
                'total_amount' => (float) $this->total_amount,
                'formatted_total' => '₹' . number_format($this->total_amount, 2),
            ],
            'items_count' => (int) $this->items()->count(),
            'submitted_at' => $this->submitted_at ? $this->submitted_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
