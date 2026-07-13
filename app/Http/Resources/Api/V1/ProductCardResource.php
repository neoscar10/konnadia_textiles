<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Portal\CustomerPricingService;
use App\Services\Portal\ProductAvailabilityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        
        $pricingService = app(CustomerPricingService::class);
        $pricing = $pricingService->calculateCustomerPrice($this->resource, $user);

        $availService = app(ProductAvailabilityService::class);
        $availability = $availService->getProductAvailability($this->resource);

        $primaryImage = $this->primaryMedia ? $this->primaryMedia->file_path : null;
        if (!$primaryImage && $this->media->first()) {
            $primaryImage = $this->media->first()->file_path;
        }

        $primaryImageUrl = $primaryImage 
            ? (str_starts_with($primaryImage, 'http') ? $primaryImage : url(Storage::url($primaryImage)))
            : 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400';

        $categories = $this->categories->map(function ($cat) {
            // Build simple recursive path
            $path = $cat->name;
            $parent = $cat->parent;
            while ($parent) {
                $path = $parent->name . ' > ' . $path;
                $parent = $parent->parent;
            }

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'path' => $path,
            ];
        })->toArray();

        $baseUnit = $this->units->where('level', 1)->first();
        $unitLabel = $baseUnit ? $baseUnit->name : 'Piece';

        return [
            'id' => $this->id,
            'slug' => $this->slug ?? Str::slug($this->title),
            'title' => $this->title,
            'sku' => $this->sku,
            'product_code' => $this->product_code ?? null,
            'brand' => 'Kannodia Premium Apparel',
            'primary_image_url' => $primaryImageUrl,
            'categories' => $categories,
            'pricing' => [
                'currency' => $pricing['currency'],
                'base_price' => (float)$pricing['base_price'],
                'effective_base_price' => (float)$pricing['effective_base_price'],
                'discount_percentage' => (float)$pricing['discount_percentage'],
                'discount_source' => $pricing['discount_source'],
                'customer_price' => (float)$pricing['customer_price'],
                'unit_label' => $unitLabel,
                'formatted_customer_price' => '₹' . number_format($pricing['customer_price'], 2),
            ],
            'availability' => [
                'available_quantity' => $availability['available_quantity'],
                'status' => $availability['status'],
                'label' => $availability['label'],
                'is_purchasable' => $availability['is_purchasable'],
            ],
            'units' => [
                'base_unit' => $baseUnit ? [
                    'id' => $baseUnit->id,
                    'name' => $baseUnit->name,
                    'short_code' => $baseUnit->short_code,
                    'label' => $baseUnit->name,
                ] : null,
                'available_units_count' => $this->units->count(),
            ],
            'tax' => [
                'hsn_code' => $this->hsn_code,
                'gst_percentage' => $this->gst_percentage !== null ? (float)$this->gst_percentage : null,
            ],
            'minimum_order_quantity' => (int) ($this->minimum_order_quantity ?? 1),
            'is_active' => (bool)$this->is_active,
            'tags' => $this->tags->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])->toArray(),
        ];
    }
}
