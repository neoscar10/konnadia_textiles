<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminInventoryResource extends JsonResource
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

        $hasCombinations = $this->combinations && $this->combinations->isNotEmpty();
        $totalStock = $hasCombinations
            ? $this->combinations->sum('stock_quantity')
            : $this->stock_quantity;

        $status = 'out_of_stock';
        $stockLabel = 'Out of Stock';

        if ($totalStock > 10) {
            $status = 'in_stock';
            $stockLabel = number_format($totalStock) . ' In Stock';
        } elseif ($totalStock > 0) {
            $status = 'low_stock';
            $stockLabel = number_format($totalStock) . ' Low Stock';
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'base_price' => (float) $this->base_price,
            'formatted_base_price' => '₹' . number_format($this->base_price, 2),
            'product_type' => $this->product_type ?? 'retail',
            'is_active' => (bool) $this->is_active,
            'primary_image_url' => $primaryMediaUrl,
            'total_stock' => (int) $totalStock,
            'stock_status' => $status,
            'stock_label' => $stockLabel,
            'inventory_value' => (float) ($totalStock * $this->base_price),
            'formatted_inventory_value' => '₹' . number_format($totalStock * $this->base_price, 2),
            'has_variants' => $hasCombinations,
            'categories' => $this->categories->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
            ]),
            'combinations' => $this->combinations->map(function ($comb) {
                $qty = (int) $comb->stock_quantity;
                $combStatus = 'out_of_stock';
                if ($qty > 10) {
                    $combStatus = 'in_stock';
                } elseif ($qty > 0) {
                    $combStatus = 'low_stock';
                }

                return [
                    'id' => $comb->id,
                    'sku' => $comb->sku,
                    'combination_values' => $comb->combination_values,
                    'price' => $comb->price !== null ? (float) $comb->price : null,
                    'stock_quantity' => $qty,
                    'stock_status' => $combStatus,
                    'is_active' => (bool) $comb->is_active,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
