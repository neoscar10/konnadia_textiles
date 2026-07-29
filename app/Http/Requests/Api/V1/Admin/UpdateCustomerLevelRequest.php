<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $levelParam = $this->route('customer_level') ?? $this->route('id');
        $levelId = is_object($levelParam) ? $levelParam->id : $levelParam;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:150', Rule::unique('customer_levels', 'name')->ignore($levelId)],
            'discount_percentage' => ['sometimes', 'required', 'numeric', 'min:-100', 'max:100'],
            'default_credit_limit' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }
}
