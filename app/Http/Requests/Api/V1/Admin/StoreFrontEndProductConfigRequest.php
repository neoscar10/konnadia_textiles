<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrontEndProductConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // The category is passed in the URL; we optionally accept it in the body too
            'category_id'                              => 'sometimes|nullable|integer|exists:categories,id',
            'description'                              => 'nullable|string|max:2000',

            // Manufacturing product components — at least one required
            'components'                               => 'required|array|min:1',
            'components.*.manufacturing_product_id'    => 'required|integer|exists:manufacturing_products,id',
            'components.*.quantity'                    => 'required|integer|min:1',

            // Packaging materials — optional
            'packaging_items'                          => 'nullable|array',
            'packaging_items.*.raw_material_id'        => 'required|integer|exists:raw_materials,id',
            'packaging_items.*.quantity'               => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'components.required'                           => 'At least one manufacturing product component is required.',
            'components.min'                                => 'At least one manufacturing product component is required.',
            'components.*.manufacturing_product_id.required'=> 'Each component must specify a manufacturing product.',
            'components.*.manufacturing_product_id.exists'  => 'One or more selected manufacturing products do not exist.',
            'components.*.quantity.min'                     => 'Each component quantity must be at least 1.',
            'packaging_items.*.raw_material_id.required'    => 'Each packaging item must specify a raw material.',
            'packaging_items.*.raw_material_id.exists'      => 'One or more selected packaging materials do not exist.',
            'packaging_items.*.quantity.min'                => 'Each packaging item quantity must be at least 1.',
        ];
    }
}
