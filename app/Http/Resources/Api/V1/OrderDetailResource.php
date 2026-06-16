<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailResource extends JsonResource
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
            'customer_notes' => $this->customer_notes,
            'credit_limit_at_order' => $this->credit_limit_at_order !== null ? (float) $this->credit_limit_at_order : null,
            'available_credit_at_order' => $this->available_credit_at_order !== null ? (float) $this->available_credit_at_order : null,
            'used_credit_override_privilege' => (bool) $this->used_credit_override_privilege,
            'totals' => [
                'currency' => 'INR',
                'subtotal' => (float) $this->subtotal,
                'gst_amount' => (float) $this->gst_amount,
                'total_amount' => (float) $this->total_amount,
                'formatted_total' => '₹' . number_format($this->total_amount, 2),
            ],
            'items' => OrderItemResource::collection($this->items),
            'receipts' => PaymentReceiptResource::collection($this->receipts),
            'timeline' => $this->statusHistories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'from_status' => $history->from_status,
                    'to_status' => $history->to_status,
                    'note' => $history->note,
                    'changed_by' => [
                        'id' => $history->changed_by,
                        'name' => $history->changedBy->name ?? 'System',
                    ],
                    'created_at' => $history->created_at ? $history->created_at->toIso8601String() : null,
                ];
            }),
            'submitted_at' => $this->submitted_at ? $this->submitted_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
