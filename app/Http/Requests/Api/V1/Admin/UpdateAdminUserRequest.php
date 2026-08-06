<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $adminId = $this->route('id');

        return [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email,' . $adminId,
            'mobile_number' => 'nullable|string|max:20',
            'password' => [
                'nullable',
                'string',
                'min:6',
                function ($attribute, $value, $fail) {
                    if (!empty($value) && request()->has('password_confirmation') && request()->input('password_confirmation') !== $value) {
                        $fail('The password field confirmation does not match.');
                    }
                },
            ],
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ];
    }
}
