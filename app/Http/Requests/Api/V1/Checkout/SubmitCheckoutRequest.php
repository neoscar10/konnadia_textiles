<?php

namespace App\Http\Requests\Api\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'checkout_method' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        // Custom message for missing receipt file
        $errors = $validator->errors();
        $message = 'Validation failed.';
        
        if ($errors->has('receipt_file')) {
            $message = 'Please upload a valid payment receipt.';
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422));
    }
}
