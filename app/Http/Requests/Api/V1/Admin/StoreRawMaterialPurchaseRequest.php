<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_name' => 'required|string|max:255',
            'purchase_date' => 'required|date|before_or_equal:today',
            'invoice_number' => 'required|string|max:100',
            'raw_material_id' => 'required|exists:raw_materials,id',
            'quantity_received' => 'nullable|numeric|gt:0',
            'purchase_rate' => 'required|numeric|gt:0',
            'num_bales' => 'nullable|integer|min:1',
            'declared_bale_length' => 'nullable|numeric|gt:0',
            'all_bales_equal_length' => 'nullable|boolean',
            'individual_bale_lengths' => 'nullable|array',
            'individual_bale_lengths.*' => 'nullable|numeric|gt:0',
            'gst_included' => 'nullable|boolean',
            'gst_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_name.required' => 'Supplier Name is required.',
            'purchase_date.required' => 'Purchase Date is required.',
            'invoice_number.required' => 'Invoice Number is required.',
            'raw_material_id.required' => 'Please select a raw material.',
            'purchase_rate.required' => 'Purchase Rate is required.',
            'purchase_rate.gt' => 'Purchase Rate must be greater than zero.',
        ];
    }
}
