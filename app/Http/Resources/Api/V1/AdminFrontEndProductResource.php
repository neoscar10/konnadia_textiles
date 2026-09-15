<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Catalog\CategoryService;

class AdminFrontEndProductResource extends JsonResource
{
    /**
     * The leaf category object (injected when returning category-centric views).
     */
    public ?object $leafCategory = null;

    public static function withCategory(object $leafCat): self
    {
        $instance = new self(null);
        $instance->leafCategory = $leafCat;
        return $instance;
    }

    public function toArray(Request $request): array
    {
        // If a leafCategory was injected (from the index list view), use it directly
        if ($this->leafCategory !== null) {
            $cat    = $this->leafCategory;
            $config = $cat->config ?? null;

            return [
                'category_id'       => $cat->id,
                'category_name'     => $cat->name,
                'category_full_path'=> $cat->full_path,
                'is_configured'     => $cat->is_configured,
                'front_end_product' => $config ? $this->formatConfig($config) : null,
            ];
        }

        // Normal resource transform for a FrontEndProduct model
        return $this->formatConfig($this->resource);
    }

    private function formatConfig($fep): array
    {
        $categoryService = app(CategoryService::class);
        $fullPath        = null;

        if ($fep->relationLoaded('category') && $fep->category) {
            $fullPath = $categoryService->buildPath($fep->category);
        } elseif ($fep->leaf_category_name) {
            $fullPath = $fep->leaf_category_name;
        }

        return [
            'id'                        => $fep->id,
            'sku'                       => $fep->sku,
            'name'                      => $fep->name,
            'category_id'               => $fep->category_id,
            'category_name'             => $fep->category?->name ?? $fep->leaf_category_name,
            'category_full_path'        => $fullPath,
            'leaf_category_name'        => $fep->leaf_category_name,
            'is_active'                 => (bool) $fep->is_active,
            'description'               => $fep->description,
            'is_configured'             => $fep->relationLoaded('components') && $fep->components->count() > 0,

            'components'                => $fep->relationLoaded('components')
                ? $fep->components->map(fn ($c) => [
                    'id'                    => $c->id,
                    'manufacturing_product_id' => $c->manufacturing_product_id,
                    'manufacturing_product'    => $c->relationLoaded('manufacturingProduct')
                        ? [
                            'id'   => $c->manufacturingProduct?->id,
                            'name' => $c->manufacturingProduct?->name,
                            'code' => $c->manufacturingProduct?->code,
                        ]
                        : null,
                    'quantity'              => $c->quantity,
                ])
                : [],

            'packaging_items'           => $fep->relationLoaded('packagingItems')
                ? $fep->packagingItems->map(fn ($p) => [
                    'id'              => $p->id,
                    'raw_material_id' => $p->raw_material_id,
                    'raw_material'    => $p->relationLoaded('rawMaterial')
                        ? [
                            'id'   => $p->rawMaterial?->id,
                            'name' => $p->rawMaterial?->name,
                            'code' => $p->rawMaterial?->code,
                        ]
                        : null,
                    'quantity'        => $p->quantity,
                ])
                : [],

            'finished_goods_batches_count' => $fep->finished_goods_batches_count ?? null,

            'created_at'                => $fep->created_at?->toIso8601String(),
            'updated_at'                => $fep->updated_at?->toIso8601String(),
        ];
    }
}
