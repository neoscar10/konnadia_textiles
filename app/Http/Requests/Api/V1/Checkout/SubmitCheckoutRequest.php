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
            'checkout_method' => ['required', 'string', 'in:manual_payment,credit'],
            'receipt_file' => ['required_if:checkout_method,manual_payment', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_file.required_if' => 'The receipt file field is required when checkout method is manual payment.',
            'receipt_file.mimes' => 'The receipt file must be a file of type: jpg, jpeg, png, webp, pdf.',
            'receipt_file.max' => 'The receipt file must not be larger than 5MB.',
        ];
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
