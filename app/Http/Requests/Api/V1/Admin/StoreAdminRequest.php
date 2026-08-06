<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'password' => [
                'required',
                'string',
                'min:6',
                function ($attribute, $value, $fail) {
                    if (request()->has('password_confirmation') && request()->input('password_confirmation') !== $value) {
                        $fail('The password field confirmation does not match.');
                    }
                },
            ],
            'is_active' => ['boolean'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ];
    }
}
