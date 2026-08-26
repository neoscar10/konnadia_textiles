<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OpenInventoryBaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bale_roll_count' => 'required|integer|min:1|max:50',
            'bale_roll_lengths' => 'required|array|min:1',
            'bale_roll_lengths.*' => 'required|numeric|gt:0',
        ];
    }

    public function messages(): array
    {
        return [
            'bale_roll_count.required' => 'Bale roll count is required.',
            'bale_roll_count.min' => 'Bale roll count must be at least 1.',
            'bale_roll_lengths.required' => 'Roll lengths array is required.',
            'bale_roll_lengths.*.gt' => 'Each roll length must be greater than zero.',
        ];
    }
}
