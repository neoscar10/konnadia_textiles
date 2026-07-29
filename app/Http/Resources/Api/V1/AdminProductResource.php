<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryMedia = $this->primaryMedia ?: $this->media->first();
        $primaryMediaUrl = $primaryMedia ? Storage::url($primaryMedia->file_path) : null;

        // Calculate total stock across variants or main product stock
        $totalStock = $this->stock_quantity;
        if ($this->relationLoaded('combinations') && $this->combinations->isNotEmpty()) {
            $totalStock = $this->combinations->sum(function ($comb) {
                return $comb->stock_quantity ?? 0;
            });
        }

        $lvl1Unit = $this->units->where('level', 1)->first();
        $lvl2Unit = $this->units->where('level', 2)->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'base_price' => (float) $this->base_price,
            'formatted_base_price' => '₹' . number_format($this->base_price, 2),
            'hsn_code' => $this->hsn_code,
            'gst_percentage' => $this->gst_percentage !== null ? (float) $this->gst_percentage : null,
            'minimum_order_quantity' => (int) $this->minimum_order_quantity,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'product_type' => $this->product_type ?? 'retail',
            'stock_quantity' => $totalStock !== null ? (int) $totalStock : null,
            'is_in_stock' => $totalStock === null || $totalStock > 0,
            'primary_image_url' => $primaryMediaUrl,
            'categories' => $this->categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'slug' => $cat->slug,
                ];
            }),
            'units' => [
                'level1' => $lvl1Unit ? [
                    'name' => $lvl1Unit->name,
                    'short_code' => $lvl1Unit->short_code,
                ] : null,
                'level2' => $lvl2Unit ? [
                    'name' => $lvl2Unit->name,
                    'short_code' => $lvl2Unit->short_code,
                    'conversion_to_base' => (float) $lvl2Unit->conversion_to_base,
                ] : null,
            ],
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
