<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaborRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:labors,code',
            'mobile_number' => 'nullable|string|max:20',
            'status' => 'nullable|boolean',
            'payment_method' => 'required|in:monthly_salary,job_work',
            'monthly_salary' => 'required_if:payment_method,monthly_salary|nullable|numeric|min:0',
            'authorized_tasks' => 'nullable|array',
            'authorized_tasks.*' => 'exists:tasks,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Labor full name is required.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Payment method must be either monthly_salary or job_work.',
            'monthly_salary.required_if' => 'Monthly salary is required when payment method is monthly_salary.',
            'monthly_salary.numeric' => 'Monthly salary must be a valid number.',
            'authorized_tasks.array' => 'Authorized tasks must be an array of task IDs.',
            'authorized_tasks.*.exists' => 'Selected task ID is invalid.',
        ];
    }
}
