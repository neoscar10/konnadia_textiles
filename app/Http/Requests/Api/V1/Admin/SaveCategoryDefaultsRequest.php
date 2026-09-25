<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveCategoryDefaultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'hsn_code' => 'nullable|string|max:20',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'minimum_order_quantity' => 'required|integer|min:1',
            'product_type' => 'required|string|in:retail,manufactured',

            'units' => 'required|array',
            'units.level1_name' => 'required|string|max:50',
            'units.level1_code' => 'required|string|max:20',
            'units.level2_name' => 'nullable|string|max:50',
            'units.level2_code' => 'nullable|string|max:20',
            'units.level2_conversion' => 'nullable|numeric|min:0.0001',

            'pricingOverrides' => 'nullable|array',

            'components' => 'nullable|array',
            'components.*.manufacturing_product_id' => 'required_with:components|integer|exists:manufacturing_products,id',
            'components.*.quantity' => 'required_with:components|integer|min:1',

            'packaging_items' => 'nullable|array',
            'packaging_items.*.raw_material_id' => 'required_with:packaging_items|integer|exists:raw_materials,id',
            'packaging_items.*.quantity' => 'required_with:packaging_items|integer|min:1',
        ];
    }
}
