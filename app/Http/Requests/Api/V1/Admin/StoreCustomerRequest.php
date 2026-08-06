<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_level_id' => ['required', 'exists:customer_levels,id'],
            'company_name' => ['required', 'string', 'max:180'],
            'gst_number' => ['required', 'string', 'max:30', 'unique:customers,gst_number'],
            'contact_person' => ['required', 'string', 'max:150'],
            'mobile_number' => [
                'required',
                'string',
                'max:30',
                'unique:customers,mobile_number',
                'unique:users,mobile_number'
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                'unique:customers,email',
                'unique:users,email'
            ],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'allow_credit_beyond_limit' => ['boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'password_mode' => ['nullable', 'string', Rule::in(['auto', 'manual'])],
            'password' => [
                'required_if:password_mode,manual',
                'nullable',
                'string',
                'min:8',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && request()->has('password_confirmation') && request()->input('password_confirmation') !== $value) {
                        $fail('The password field confirmation does not match.');
                    }
                },
            ],
        ];
    }
}
