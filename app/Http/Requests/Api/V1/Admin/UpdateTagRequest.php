<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tagId = $this->route('id') ?: $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name')->ignore($tagId),
            ],
            'category_ids' => 'sometimes|required|array|min:1',
            'category_ids.*' => 'exists:categories,id',
            'include_descendants' => 'nullable|boolean',
        ];
    }
}
