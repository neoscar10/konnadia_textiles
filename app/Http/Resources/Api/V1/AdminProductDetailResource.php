<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->loadMissing([
            'categories', 'media', 'primaryMedia', 'variationGroups.values.media',
            'combinations', 'customerLevelPrices.customerLevel', 'units', 'tags',
        ]);

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
            'stock_quantity' => $this->stock_quantity !== null ? (int) $this->stock_quantity : null,
            'categories' => $this->categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'slug' => $cat->slug,
                ];
            }),
            'tags' => $this->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ];
            }),
            'media' => $this->media->map(function ($m) {
                return [
                    'id' => $m->id,
                    'file_path' => $m->file_path,
                    'file_url' => Storage::url($m->file_path),
                    'file_type' => $m->file_type,
                    'mime_type' => $m->mime_type,
                    'size' => $m->size,
                    'sort_order' => (int) $m->sort_order,
                    'is_primary' => (bool) $m->is_primary,
                ];
            }),
            'units' => [
                'level1_name' => $lvl1Unit ? $lvl1Unit->name : 'Piece',
                'level1_code' => $lvl1Unit ? $lvl1Unit->short_code : 'pcs',
                'level2_name' => $lvl2Unit ? $lvl2Unit->name : '',
                'level2_code' => $lvl2Unit ? $lvl2Unit->short_code : '',
                'level2_conversion' => $lvl2Unit ? (float) $lvl2Unit->conversion_to_base : null,
            ],
            'customer_level_prices' => $this->customerLevelPrices->map(function ($price) {
                return [
                    'id' => $price->id,
                    'customer_level_id' => $price->customer_level_id,
                    'customer_level_name' => $price->customerLevel->name ?? 'N/A',
                    'discount_percentage' => (float) $price->discount_percentage,
                ];
            }),
            'variation_groups' => $this->variationGroups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'display_type' => $group->display_type,
                    'has_images' => (bool) $group->has_images,
                    'sort_order' => (int) $group->sort_order,
                    'values' => $group->values->map(function ($val) {
                        return [
                            'id' => $val->id,
                            'value' => $val->value,
                            'color_hex' => $val->color_hex,
                            'is_default' => (bool) $val->is_default,
                            'sort_order' => (int) $val->sort_order,
                            'media' => $val->media->map(fn($m) => [
                                'id' => $m->id,
                                'file_path' => $m->file_path,
                                'file_url' => Storage::url($m->file_path),
                            ]),
                        ];
                    }),
                ];
            }),
            'combinations' => $this->combinations->map(function ($comb) {
                return [
                    'id' => $comb->id,
                    'sku' => $comb->sku,
                    'combination_values' => $comb->combination_values,
                    'price' => $comb->price !== null ? (float) $comb->price : null,
                    'stock_quantity' => $comb->stock_quantity !== null ? (int) $comb->stock_quantity : null,
                    'is_active' => (bool) $comb->is_active,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->format('d-M-Y H:i') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d-M-Y H:i') : null,
        ];
    }
}
