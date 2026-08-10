<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('task');

        return [
            'name' => "required|string|max:255|unique:tasks,name,{$id}",
            'code' => "nullable|string|max:50|unique:tasks,code,{$id}",
            'status' => 'nullable|boolean',
            'consumes_raw_material' => 'required|boolean',
            'is_labor_required' => 'required|boolean',
            'selected_category_ids' => 'required_if:consumes_raw_material,true|array',
            'selected_category_ids.*' => 'exists:raw_material_categories,id',
            'sequence_number' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Task Name is required.',
            'name.unique' => 'Task Name has already been taken.',
            'code.unique' => 'Task Code has already been taken.',
            'selected_category_ids.required_if' => 'Please select at least one raw material category when raw material consumption is enabled.',
        ];
    }
}
