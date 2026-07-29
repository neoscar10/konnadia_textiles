<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Services\Catalog\CategoryService;
use App\Services\Portal\ProductAvailabilityService;

class AdminDesignCatalogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryService = app(CategoryService::class);
        $availabilityService = app(ProductAvailabilityService::class);

        $primaryMedia = $this->primaryMedia ?: $this->media->first();
        $primaryMediaUrl = $primaryMedia ? Storage::url($primaryMedia->file_path) : null;

        // Build category breadcrumb paths
        $categoryPaths = [];
        foreach ($this->categories as $category) {
            $categoryPaths[] = $categoryService->buildPath($category);
        }

        // Compute stock details
        $availability = $availabilityService->getProductAvailability($this->resource);
        $computedStock = $availability['available_quantity'];
        $stockStatus = $availability['status'];
        
        $stockLabel = 'Out of Stock';
        if ($computedStock === PHP_INT_MAX) {
            $stockLabel = 'Unlimited';
        } elseif ($computedStock > 10) {
            $stockLabel = number_format($computedStock) . ' In Stock';
        } elseif ($computedStock > 0) {
            $stockLabel = number_format($computedStock) . ' Low Stock';
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'base_price' => (float) $this->base_price,
            'formatted_base_price' => '₹' . number_format($this->base_price, 2),
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'primary_image_url' => $primaryMediaUrl,
            'category_paths' => $categoryPaths,
            'categories' => $this->categories->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'slug' => $c->slug,
            ]),
            'tags' => $this->tags->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ]),
            'stock_details' => [
                'computed_stock' => $computedStock === PHP_INT_MAX ? null : $computedStock,
                'is_unlimited' => $computedStock === PHP_INT_MAX,
                'stock_status' => $stockStatus,
                'stock_label' => $stockLabel,
            ],
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
        ];
    }
}
