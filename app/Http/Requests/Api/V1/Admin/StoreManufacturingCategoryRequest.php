<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreManufacturingCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:manufacturing_product_categories,name',
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
