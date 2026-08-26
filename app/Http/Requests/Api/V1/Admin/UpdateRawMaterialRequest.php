<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('raw_material');

        return [
            'raw_material_category_id' => 'required|exists:raw_material_categories,id',
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('raw_materials', 'code')->ignore($id)],
            'unit_group_id' => 'nullable|exists:unit_groups,id',
            'unit_id' => 'nullable|exists:units,id',
            'unit' => 'required|string|max:50',
            'standard_width' => 'nullable|numeric|min:0',
            'width_unit' => 'nullable|in:inch,cm',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'raw_material_category_id.required' => 'Category is required.',
            'name.required' => 'Raw Material Name is required.',
            'code.unique' => 'Raw Material Code must be unique.',
            'unit.required' => 'Unit of Measurement is required.',
        ];
    }
}
