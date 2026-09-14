<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:raw_material_categories,name',
            'code' => 'nullable|string|max:50|unique:raw_material_categories,code',
            'unit_type' => 'required|string|in:length_based,other',
            'unit_group_id' => 'nullable|exists:unit_groups,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ];
    }
}
