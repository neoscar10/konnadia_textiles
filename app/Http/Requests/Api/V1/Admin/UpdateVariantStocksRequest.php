<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantStocksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'variant_stocks' => 'required|array|min:1',
            'variant_stocks.*.combination_id' => 'required|exists:product_combinations,id',
            'variant_stocks.*.stock_quantity' => 'required|integer|min:0',
        ];
    }
}
