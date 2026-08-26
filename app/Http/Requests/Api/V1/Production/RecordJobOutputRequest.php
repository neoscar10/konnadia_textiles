<?php

namespace App\Http\Requests\Api\V1\Production;

use Illuminate\Foundation\Http\FormRequest;

class RecordJobOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completed_quantity' => ['required', 'integer', 'min:0'],
            'rejected_quantity' => ['nullable', 'integer', 'min:0'],
            'damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_final_step_completion' => ['nullable', 'boolean'],
            
            // Raw material consumption / fabric batch details
            'inventory_batch_id' => ['nullable', 'integer', 'exists:inventory_batches,id'],
            'raw_material_consumptions' => ['nullable', 'array'],
            'raw_material_consumptions.*.inventory_batch_id' => ['required_with:raw_material_consumptions', 'integer', 'exists:inventory_batches,id'],
            'raw_material_consumptions.*.quantity_consumed' => ['required_with:raw_material_consumptions', 'numeric', 'min:0'],
            'raw_material_consumptions.*.wastage_quantity' => ['nullable', 'numeric', 'min:0'],

            // Cutting stage specific roll fields
            'cutting_rolls' => ['nullable', 'array'],
            'cutting_rolls.*.inventory_batch_id' => ['required_with:cutting_rolls', 'integer', 'exists:inventory_batches,id'],
            'cutting_rolls.*.cut_length' => ['required_with:cutting_rolls', 'numeric', 'min:0'],
            'cutting_rolls.*.wastage' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
