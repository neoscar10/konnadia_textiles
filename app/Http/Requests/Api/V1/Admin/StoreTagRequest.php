<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100|unique:tags,name',
            'category_ids' => 'required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'include_descendants' => 'nullable|boolean',
        ];
    }
}
