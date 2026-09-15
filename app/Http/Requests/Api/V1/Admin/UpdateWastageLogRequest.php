<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWastageLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_code'                  => 'sometimes|nullable|string|max:100',
            'production_job_id'         => 'sometimes|nullable|integer|exists:production_jobs,id',
            'manufacturing_product_id'  => 'sometimes|nullable|integer|exists:manufacturing_products,id',
            'pattern_id'                => 'sometimes|nullable|integer|exists:manufacturing_product_patterns,id',
            'inventory_bale_roll_id'    => 'sometimes|nullable|integer|exists:inventory_bale_rolls,id',
            'task_id'                   => 'sometimes|nullable|integer|exists:tasks,id',
            'wastage_type'              => 'sometimes|required|string|in:scrap,damage,damaged',
            'quantity_wasted'           => 'sometimes|required|numeric|min:0.01',
            'reason'                    => 'sometimes|nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'wastage_type.in'     => 'Wastage type must be one of: scrap, damage, or damaged.',
            'quantity_wasted.min' => 'Quantity wasted must be at least 0.01.',
        ];
    }
}
