<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        $imageUrl = null;
        if ($product) {
            $primaryImage = $product->primaryMedia ? $product->primaryMedia->file_path : null;
            if (!$primaryImage && $product->media->first()) {
                $primaryImage = $product->media->first()->file_path;
            }
            $imageUrl = $primaryImage
                ? (str_starts_with($primaryImage, 'http') ? $primaryImage : url(Storage::url($primaryImage)))
                : url('/images/product-placeholder.svg');
        } else {
            $imageUrl = url('/images/product-placeholder.svg');
        }

        $conversionToBase = (float) ($this->unit_conversion_quantity ?: 1.0);
        $baseQty = (int) ($this->quantity * $conversionToBase);

        $statusValue = $this->status ?: 'pending_dispatch';
        $statusLabel = match($statusValue) {
            'dispatched' => 'Dispatched',
            'partially_dispatched' => 'Partially Dispatched',
            'allocated' => 'Allocated',
            'cancelled' => 'Cancelled',
            default => 'Pending Dispatch',
        };

        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product_id,
                'title' => $this->product_title,
                'sku' => $this->product_sku,
                'image_url' => $imageUrl,
            ],
            'combination' => $this->product_combination_id ? [
                'id' => $this->product_combination_id,
                'label' => $this->selected_options ? implode(' / ', $this->selected_options) : '',
                'values' => $this->selected_options,
            ] : null,
            'unit' => [
                'name' => $this->unit_name ?: 'Piece',
                'short_code' => $this->unit_short_code ?: 'Pcs',
                'conversion_to_base' => $conversionToBase,
                'label' => ($this->unit_name ?: 'Piece') . ' (' . round($conversionToBase) . ' ' . ($conversionToBase == 1 ? 'Pc' : 'Pcs') . ')',
            ],
            'quantity' => (int) $this->quantity,
            'quantity_lvl1' => $this->quantity_lvl1 !== null ? (float)$this->quantity_lvl1 : null,
            'quantity_lvl2' => $this->quantity_lvl2 !== null ? (float)$this->quantity_lvl2 : null,
            'base_quantity' => $baseQty,
            'status' => [
                'value' => $statusValue,
                'label' => $statusLabel,
            ],
            'dispatch' => [
                'dispatch_number' => $this->dispatch_number,
                'dispatch_note' => $this->dispatch_note,
                'dispatched_at' => $this->dispatched_at ? $this->dispatched_at->toIso8601String() : null,
                'dispatched_at_formatted' => $this->dispatched_at ? $this->dispatched_at->format('M d, Y g:i A') : null,
            ],
            'hsn_code' => $this->hsn_code,
            'pricing' => [
                'base_unit_price' => (float) $this->base_unit_price,
                'customer_unit_price' => (float) $this->customer_unit_price,
                'line_subtotal' => (float) $this->line_subtotal,
                'gst_percentage' => (float) $this->gst_percentage,
                'gst_amount' => (float) $this->gst_amount,
                'line_total' => (float) $this->line_total,
                'formatted_line_total' => '₹' . number_format($this->line_total, 2),
            ]
        ];
    }
}
