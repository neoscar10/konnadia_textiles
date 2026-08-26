<?php

namespace App\Http\Requests\Api\V1\Production;

use Illuminate\Foundation\Http\FormRequest;

class ConvertFinishedGoodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_product_id' => ['required', 'integer', 'exists:products,id'],
            'target_unit_level' => ['required', 'integer', 'in:1,2'],
            'conversion_notes' => ['nullable', 'string', 'max:500'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.production_job_id' => ['required', 'integer', 'exists:production_jobs,id'],
            'components.*.quantity_per_set' => ['required', 'numeric', 'min:0.01'],
            'components.*.total_pieces_input' => ['required', 'integer', 'min:1'],
            'packaging' => ['nullable', 'array'],
            'packaging.*.raw_material_id' => ['required_with:packaging', 'integer', 'exists:raw_materials,id'],
            'packaging.*.quantity_used' => ['required_with:packaging', 'numeric', 'min:0.01'],
        ];
    }
}
