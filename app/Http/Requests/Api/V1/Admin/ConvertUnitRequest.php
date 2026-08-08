<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ConvertUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_unit' => 'required|string',
            'to_unit' => 'required|string',
            'quantity' => 'required|numeric|min:0',
            'unit_group_id' => 'nullable|integer|exists:unit_groups,id',
        ];
    }
}
