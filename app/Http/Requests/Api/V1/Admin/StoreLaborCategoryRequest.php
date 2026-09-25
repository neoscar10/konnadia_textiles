<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaborCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:labor_categories,name',
            'code' => 'nullable|string|max:50|unique:labor_categories,code',
            'description' => 'nullable|string',
            'status' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Labour Category Name is required.',
            'name.unique' => 'A Labour Category with this name already exists.',
            'code.unique' => 'A Labour Category with this code already exists.',
        ];
    }
}
