<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id') ?: $this->route('product');

        return [
            'title' => 'required|string|max:200',
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $productId,
            'base_price' => 'required|numeric|min:0',
            'description' => 'required|string',
            'hsn_code' => 'nullable|string|max:20',
            'gst_percentage' => 'nullable|numeric|min:0|max:100',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'product_type' => 'required|string|in:retail,manufactured',
            'is_active' => 'nullable|boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            
            // Category & Tag attachments
            'category_ids' => 'sometimes|required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',

            // Dual Unit Config
            'units' => 'nullable|array',
            'units.level1_name' => 'required_with:units|string|max:50',
            'units.level1_code' => 'required_with:units|string|max:20',
            'units.level2_name' => 'nullable|string|max:50',
            'units.level2_code' => 'nullable|string|max:20',
            'units.level2_conversion' => 'nullable|numeric|min:0.0001',

            // Customer Level Discount Overrides: [{customer_level_id: 1, discount_percentage: 10}]
            'customer_level_prices' => 'nullable|array',
            'customer_level_prices.*.customer_level_id' => 'required_with:customer_level_prices|exists:customer_levels,id',
            'customer_level_prices.*.discount_percentage' => 'required_with:customer_level_prices|numeric|between:-100,100',

            // Variation groups & values setup
            'variation_groups' => 'nullable|array',
            'variation_groups.*.name' => 'required_with:variation_groups|string|max:100',
            'variation_groups.*.display_type' => 'nullable|string|in:text,color,image',
            'variation_groups.*.has_images' => 'nullable|boolean',
            'variation_groups.*.values' => 'required_with:variation_groups|array|min:1',
            'variation_groups.*.values.*.value' => 'required|string|max:100',
            'variation_groups.*.values.*.color_hex' => 'nullable|string|max:20',
            'variation_groups.*.values.*.is_default' => 'nullable|boolean',

            // Generated combinations stock & price config
            'combinations' => 'nullable|array',
            'combinations.*.combination_values' => 'required_with:combinations|array',
            'combinations.*.sku' => 'nullable|string|max:100',
            'combinations.*.price' => 'nullable|numeric|min:0',
            'combinations.*.stock_quantity' => 'nullable|integer|min:0',
            'combinations.*.is_active' => 'nullable|boolean',

            // Optional uploaded media files array
            'images' => 'nullable|array',
            'images.*' => 'image|max:4096',
        ];
    }
}
