<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinishedGoodsConversionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1 fields
            'category_id'                                       => 'required|integer|exists:categories,id',
            'target_qty'                                        => 'required|integer|min:1',
            'design_type'                                       => 'required|in:new,existing',
            'design_id'                                         => 'required_if:design_type,new|nullable|string|max:100',
            'existing_storefront_product_id'                    => 'required_if:design_type,existing|nullable|integer|exists:products,id',
            'notes'                                             => 'nullable|string|max:2000',
            'reuse_cutting_photo'                               => 'nullable|boolean',

            // Component pattern allocations — array indexed by component index
            // Each top-level key is a component index (0, 1, 2...)
            // Each value is an array of pattern rows
            'component_selections'                              => 'nullable|array',
            'component_selections.*'                            => 'array',
            'component_selections.*.*.pattern_id'               => 'nullable|integer',
            'component_selections.*.*.quantity'                 => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required'                   => 'Please select a leaf category.',
            'category_id.exists'                     => 'The selected category does not exist.',
            'target_qty.min'                         => 'Target quantity must be at least 1.',
            'design_type.in'                         => 'Design type must be either "new" or "existing".',
            'design_id.required_if'                  => 'A Design ID is required when creating a new design.',
            'existing_storefront_product_id.required_if' => 'Please select an existing storefront product.',
            'existing_storefront_product_id.exists'  => 'The selected storefront product does not exist.',
        ];
    }
}
