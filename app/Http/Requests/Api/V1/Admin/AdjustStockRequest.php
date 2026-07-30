<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'combination_id' => 'nullable|exists:product_combinations,id',
            'adjustment_type' => 'required|string|in:set,add,deduct',
            'quantity' => 'required|integer|min:0',
            'reason' => 'nullable|string|max:255',
        ];
    }
}
