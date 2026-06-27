<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Portal\CustomerPricingService;
use App\Services\Portal\ProductAvailabilityService;
use App\Services\Portal\ProductUnitPricingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductDetailResource extends JsonResource
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

        $unitPricingService = app(ProductUnitPricingService::class);
        $units = $unitPricingService->getAvailableUnits($this->resource, $pricing['customer_price']);

        $media = $this->media->map(fn($m) => [
            'id' => $m->id,
            'url' => str_starts_with($m->file_path, 'http') ? $m->file_path : url(Storage::url($m->file_path)),
            'type' => $m->file_type ?? 'image',
            'mime_type' => $m->mime_type ?? 'image/jpeg',
            'is_primary' => (bool)$m->is_primary,
            'sort_order' => $m->sort_order,
            'alt_text' => $m->alt_text ?? $this->title,
        ])->toArray();

        if (empty($media)) {
            $media[] = [
                'id' => 0,
                'url' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=800',
                'type' => 'image',
                'mime_type' => 'image/jpeg',
                'is_primary' => true,
                'sort_order' => 1,
                'alt_text' => $this->title,
            ];
        }

        $categories = $this->categories->map(function ($cat) {
            $path = $cat->name;
            $parent = $cat->parent;
            while ($parent) {
                $path = $parent->name . ' > ' . $path;
                $parent = $parent->parent;
            }

            return [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => Str::slug($cat->name),
                'path' => $path,
            ];
        })->toArray();

        // Breadcrumb array
        $breadcrumb = [
            ['label' => 'Home', 'type' => 'home'],
            ['label' => 'Products', 'type' => 'products'],
        ];
        if (!empty($categories)) {
            $cat = $this->categories->first();
            if ($cat->parent) {
                $breadcrumb[] = [
                    'id' => $cat->parent->id,
                    'label' => $cat->parent->name,
                    'slug' => Str::slug($cat->parent->name),
                    'type' => 'category',
                ];
            }
            $breadcrumb[] = [
                'id' => $cat->id,
                'label' => $cat->name,
                'slug' => Str::slug($cat->name),
                'type' => 'category',
            ];
        }
        $breadcrumb[] = ['label' => $this->title, 'type' => 'product'];

        $variations = $this->variationGroups->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'display_type' => $g->display_type,
            'has_images' => (bool)$g->has_images,
            'values' => $g->values->map(fn($v) => [
                'id' => $v->id,
                'value' => $v->value,
                'color_hex' => $v->color_hex,
                'is_default' => (bool)$v->is_default,
                'media' => $v->media->map(fn($vm) => [
                    'id' => $vm->id,
                    'url' => str_starts_with($vm->file_path, 'http') ? $vm->file_path : url(Storage::url($vm->file_path)),
                    'sort_order' => $vm->sort_order,
                ])->toArray(),
            ])->toArray(),
        ])->toArray();

        $combinations = $this->combinations->map(function ($c) use ($pricingService, $availService, $user) {
            $combPrice = $pricingService->calculateCustomerPrice($this->resource, $user, $c);
            $combAvail = $availService->getCombinationAvailability($c);

            // Match value IDs
            $valueIds = [];
            foreach ($c->combination_values as $groupName => $val) {
                foreach ($this->variationGroups as $g) {
                    if ($g->name === $groupName) {
                        foreach ($g->values as $v) {
                            if ($v->value === $val) {
                                $valueIds[] = $v->id;
                            }
                        }
                    }
                }
            }

            return [
                'id' => $c->id,
                'label' => implode(' / ', array_values($c->combination_values)),
                'values' => $c->combination_values,
                'value_ids' => $valueIds,
                'sku' => $c->sku,
                'price' => [
                    'effective_base_price' => (float)$combPrice['effective_base_price'],
                    'customer_price' => (float)$combPrice['customer_price'],
                    'formatted_customer_price' => '₹' . number_format($combPrice['customer_price'], 2),
                ],
                'availability' => [
                    'available_quantity' => $combAvail['available_quantity'],
                    'status' => $combAvail['status'],
                    'label' => $combAvail['label'],
                    'is_purchasable' => $combAvail['is_purchasable'],
                ],
                'is_active' => (bool)$c->is_active,
            ];
        })->toArray();

        $formattedUnits = collect($units)->map(fn($u) => [
            'id' => $u['id'],
            'level' => $u['level'],
            'name' => $u['name'],
            'short_code' => $u['short_code'],
            'conversion_to_base' => (float)$u['conversion_to_base'],
            'price' => (float)$u['price'],
            'formatted_price' => '₹' . number_format($u['price'], 2),
            'label' => $u['label'],
        ])->toArray();

        return [
            'id' => $this->id,
            'slug' => $this->slug ?? Str::slug($this->title),
            'title' => $this->title,
            'sku' => $this->sku,
            'product_code' => $this->product_code ?? null,
            'brand' => 'Kannodia Premium Apparel',
            'status' => $this->is_active ? 'active' : 'inactive',
            'description' => [
                'markdown' => $this->description,
                'html' => Str::markdown($this->description ?? ''),
                'plain_text' => strip_tags(Str::markdown($this->description ?? '')),
            ],
            'media' => $media,
            'categories' => $categories,
            'breadcrumb' => $breadcrumb,
            'pricing' => [
                'currency' => $pricing['currency'],
                'base_price' => (float)$pricing['base_price'],
                'effective_base_price' => (float)$pricing['effective_base_price'],
                'discount_percentage' => (float)$pricing['discount_percentage'],
                'discount_source' => $pricing['discount_source'],
                'customer_price' => (float)$pricing['customer_price'],
                'formatted_customer_price' => '₹' . number_format($pricing['customer_price'], 2),
            ],
            'availability' => [
                'available_quantity' => $availability['available_quantity'],
                'status' => $availability['status'],
                'label' => $availability['label'],
                'is_purchasable' => $availability['is_purchasable'],
            ],
            'units' => $formattedUnits,
            'variations' => $variations,
            'combinations' => $combinations,
            'purchase_defaults' => [
                'selected_unit_id' => !empty($formattedUnits) ? $formattedUnits[0]['id'] : null,
                'quantity' => (int) ($this->minimum_order_quantity ?? 10),
                'minimum_order_quantity' => (int) ($this->minimum_order_quantity ?? 1),
            ],
            'tax' => [
                'hsn_code' => $this->hsn_code,
                'gst_percentage' => $this->gst_percentage !== null ? (float)$this->gst_percentage : null,
            ]
        ];
    }
}
