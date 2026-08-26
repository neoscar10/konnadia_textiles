<?php

namespace App\Http\Requests\Api\V1\Production;

use Illuminate\Foundation\Http\FormRequest;

class AssignJobLaborersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labor_allocations' => ['required', 'array', 'min:1'],
            'labor_allocations.*.labor_id' => ['required', 'integer', 'exists:labors,id'],
            'labor_allocations.*.rate_per_piece' => ['nullable', 'numeric', 'min:0'],
            'labor_allocations.*.assigned_quantity' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
