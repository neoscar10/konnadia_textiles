<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $product = $this->product;
        
        $primaryImage = $product->primaryMedia ? $product->primaryMedia->file_path : null;
        if (!$primaryImage && $product->media->first()) {
            $primaryImage = $product->media->first()->file_path;
        }
        
        $imageUrl = $primaryImage
            ? (str_starts_with($primaryImage, 'http') ? $primaryImage : url(Storage::url($primaryImage)))
            : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=160';

        $availService = app(\App\Services\Portal\ProductAvailabilityService::class);
        $avail = $this->combination 
            ? $availService->getCombinationAvailability($this->combination)
            : $availService->getProductAvailability($product);

        $conversionToBase = (float) ($this->unit ? $this->unit->conversion_to_base : 1.0);
        $baseQty = (int) ($this->quantity * $conversionToBase);

        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product_id,
                'slug' => $product->slug ?? Str::slug($product->title),
                'title' => $product->title,
                'sku' => $product->sku,
                'image_url' => $imageUrl,
            ],
            'combination' => $this->combination ? [
                'id' => $this->product_combination_id,
                'label' => implode(' / ', $this->combination->combination_values),
                'values' => $this->combination->combination_values,
            ] : null,
            'unit' => $this->unit ? [
                'id' => $this->product_unit_id,
                'name' => $this->unit->name,
                'short_code' => $this->unit->short_code,
                'level' => (int) $this->unit->level,
                'conversion_to_base' => (float) $this->unit->conversion_to_base,
                'label' => $this->unit->name . ' (' . round($this->unit->conversion_to_base) . ' ' . ($this->unit->conversion_to_base == 1 ? 'Pc' : 'Pcs') . ')',
            ] : null,
            'quantity' => (int) $this->quantity,
            'base_quantity' => $baseQty,
            'hsn_code' => $this->hsn_code,
            'pricing' => [
                'currency' => 'INR',
                'base_unit_price' => (float) $this->base_unit_price,
                'customer_unit_price' => (float) $this->customer_unit_price,
                'line_subtotal' => (float) $this->line_subtotal,
                'gst_percentage' => (float) $this->gst_percentage,
                'gst_amount' => (float) $this->gst_amount,
                'line_total' => (float) $this->line_total,
                'formatted_line_total' => '₹' . number_format($this->line_total, 2),
            ],
            'availability' => [
                'available_quantity' => (int) $avail['available_quantity'],
                'is_available' => (bool) $avail['is_purchasable'],
                'message' => $avail['label'],
            ]
        ];
    }
}
