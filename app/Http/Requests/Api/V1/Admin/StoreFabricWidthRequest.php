<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreFabricWidthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'value' => 'required|numeric|min:0.01',
            'unit_id' => 'nullable|exists:units,id',
            'unit' => 'nullable|string|max:20',
            'status' => 'boolean',
        ];
    }
}
