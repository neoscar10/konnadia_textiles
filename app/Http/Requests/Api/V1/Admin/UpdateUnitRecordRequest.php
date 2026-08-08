<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUnitRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_group_id' => 'nullable|integer|exists:unit_groups,id',
            'name' => 'sometimes|required|string|max:255',
            'short_code' => 'sometimes|required|string|max:50',
            'is_base' => 'nullable|boolean',
            'ratio_to_base' => 'required_unless:is_base,true|numeric|gt:0',
            'is_active' => 'nullable|boolean',
        ];
    }
}
