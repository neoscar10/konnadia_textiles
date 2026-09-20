<?php

namespace App\Http\Requests\Api\V1\Production;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manufacturing_product_id' => ['nullable', 'required_without:items', 'integer', 'exists:manufacturing_products,id'],
            'planned_quantity' => ['nullable', 'required_without:items', 'integer', 'min:1'],
            'supervisor_id' => ['nullable', 'integer'],
            'factory_supervisor_id' => ['nullable', 'integer', 'exists:factory_supervisors,id'],
            'cutter_id' => ['nullable', 'integer', 'exists:labors,id'],
            'pattern_id' => ['nullable', 'integer', 'exists:manufacturing_product_patterns,id'],
            'priority' => ['nullable', 'string', 'in:Urgent,High,Normal,Low'],
            'batch_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array'],
            'items.*.manufacturing_product_id' => ['required_with:items', 'integer', 'exists:manufacturing_products,id'],
            'items.*.pattern_id' => ['nullable', 'integer', 'exists:manufacturing_product_patterns,id'],
            'items.*.planned_quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }
}

