<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRawMaterialCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('raw_material_category');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('raw_material_categories', 'name')->ignore($id)],
            'code' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('raw_material_categories', 'code')->ignore($id)],
            'unit_type' => 'sometimes|required|string|in:length_based,other',
            'unit_group_id' => 'nullable|exists:unit_groups,id',
            'base_unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ];
    }
}
