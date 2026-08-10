<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => 'required|array|min:1',
            'ordered_ids.*' => 'required|integer|exists:tasks,id',
        ];
    }

    public function messages(): array
    {
        return [
            'ordered_ids.required' => 'Ordered task IDs array is required.',
            'ordered_ids.*.exists' => 'One or more provided task IDs do not exist.',
        ];
    }
}
