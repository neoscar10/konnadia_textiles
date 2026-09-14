<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManufacturingCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('product_category');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('manufacturing_product_categories', 'name')->ignore($id),
            ],
            'status' => 'nullable|boolean',
            'default_tasks' => 'nullable|array',
            'default_tasks.*.task_id' => 'required_with:default_tasks|exists:tasks,id',
            'default_tasks.*.standard_labor_rate' => 'nullable|numeric|min:0',
            'default_tasks.*.is_final_step' => 'nullable|boolean',
            'default_tasks.*.sequence_number' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A manufacturing product category with this name already exists.',
        ];
    }
}
