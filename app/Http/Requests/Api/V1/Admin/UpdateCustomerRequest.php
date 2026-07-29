<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerParam = $this->route('customer');
        $customerId = is_object($customerParam) ? $customerParam->id : $customerParam;
        $customer = Customer::find($customerId);
        $userId = $customer?->user_id;

        return [
            'customer_level_id' => ['sometimes', 'required', 'exists:customer_levels,id'],
            'company_name' => ['sometimes', 'required', 'string', 'max:180'],
            'gst_number' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('customers', 'gst_number')->ignore($customerId)],
            'contact_person' => ['sometimes', 'required', 'string', 'max:150'],
            'mobile_number' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'mobile_number')->ignore($customerId),
                Rule::unique('users', 'mobile_number')->ignore($userId)
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('customers', 'email')->ignore($customerId),
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'allow_credit_beyond_limit' => ['boolean'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
