<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupervisorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('supervisor');

        return [
            'name' => 'sometimes|required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('factory_supervisors', 'code')->ignore($id)],
            'phone' => 'nullable|string|max:50',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('factory_supervisors', 'email')->ignore($id)],
            'department' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ];
    }
}
