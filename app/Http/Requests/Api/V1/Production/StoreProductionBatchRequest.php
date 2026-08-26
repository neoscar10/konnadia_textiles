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
            'manufacturing_product_id' => ['required', 'integer', 'exists:manufacturing_products,id'],
            'planned_quantity' => ['required', 'integer', 'min:1'],
            'supervisor_id' => ['nullable', 'integer', 'exists:users,id'],
            'batch_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
