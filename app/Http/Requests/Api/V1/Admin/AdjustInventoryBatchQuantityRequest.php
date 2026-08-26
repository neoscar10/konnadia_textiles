<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryBatchQuantityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment_type' => 'required|in:deduct,restore',
            'quantity' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'adjustment_type.required' => 'Adjustment type (deduct or restore) is required.',
            'quantity.required' => 'Adjustment quantity is required.',
            'quantity.gt' => 'Quantity must be greater than zero.',
            'reason.required' => 'Adjustment reason is required.',
        ];
    }
}
