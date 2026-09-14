<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:suppliers,name',
            'contact_person' => 'nullable|string|max:255',
            'whatsapp_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'portal_access' => 'nullable|boolean',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}
