<?php

namespace App\Http\Requests\Api\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'combination_id' => ['nullable', 'integer', 'exists:product_combinations,id'],
            'unit_id' => ['required_without_all:quantity_lvl1,quantity_lvl2', 'nullable', 'integer', 'exists:product_units,id'],
            'quantity' => ['required_without_all:quantity_lvl1,quantity_lvl2', 'nullable', 'integer', 'min:1'],
            'quantity_lvl1' => ['nullable', 'integer', 'min:0'],
            'quantity_lvl2' => ['nullable', 'integer', 'min:0'],
            'selected_options' => ['nullable', 'array'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
