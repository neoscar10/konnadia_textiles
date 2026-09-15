<?php

namespace App\Http\Requests\Api\V1\Factory;

use Illuminate\Foundation\Http\FormRequest;

class SaveOverheadAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'production_value' => ['required', 'numeric', 'min:1'],
            'material_rows' => ['nullable', 'array'],
            'material_rows.*.raw_material_id' => ['required', 'integer', 'exists:raw_materials,id'],
            'material_rows.*.closing_stock_qty' => ['required', 'numeric', 'min:0'],
            'other_overhead_rows' => ['nullable', 'array'],
            'other_overhead_rows.*.category' => ['nullable', 'string'],
            'other_overhead_rows.*.custom_category' => ['nullable', 'string'],
            'other_overhead_rows.*.amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
