<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Catalog\CategoryService;

class AdminCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryService = app(CategoryService::class);
        $defaults = $this->default_product_config;
        if (is_array($defaults)) {
            $pricing = $defaults['pricingOverrides'] ?? [];
            $defaults['pricingOverrides'] = empty($pricing) ? (object) [] : (object) $pricing;
        }

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'title' => $this->title ?? $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'is_leaf' => (bool) $this->is_leaf,
            'sort_order' => (int) $this->sort_order,
            'full_path' => $categoryService->buildPath($this->resource),
            'children_count' => $this->children()->count(),
            'products_count' => $this->products()->count(),
            'default_product_config' => $defaults,
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}