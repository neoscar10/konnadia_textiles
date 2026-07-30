<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Catalog\CategoryService;

class AdminTagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $categoryService = app(CategoryService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'categories' => $this->categories->map(function ($cat) use ($categoryService) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'title' => $cat->title,
                    'slug' => $cat->slug,
                    'is_leaf' => (bool) $cat->is_leaf,
                    'full_path' => $categoryService->buildPath($cat),
                ];
            }),
            'products_count' => $this->products()->count(),
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
