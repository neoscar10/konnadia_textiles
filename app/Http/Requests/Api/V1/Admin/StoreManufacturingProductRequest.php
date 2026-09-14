<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreManufacturingProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'manufacturing_product_category_id' => 'required|exists:manufacturing_product_categories,id',
            'code' => 'nullable|string|max:50|unique:manufacturing_products,code',
            'status' => 'nullable|in:active,inactive',
            'standard_labor_rate' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:10240',
            'is_common_subsidiary' => 'nullable|boolean',
            'is_subsidiary_used' => 'nullable|boolean',
            'subsidiary_materials' => 'nullable|array',
            'subsidiary_materials.*.raw_material_id' => 'required_with:subsidiary_materials|exists:raw_materials,id',
            'subsidiary_materials.*.consumption_quantity' => 'required_with:subsidiary_materials|numeric|min:0.0001',
            'is_stitching_used' => 'nullable|boolean',
            'stitching_materials' => 'nullable|array',
            'stitching_materials.*' => 'exists:raw_materials,id',
            'product_id' => 'nullable|exists:products,id',
            'product_combination_id' => 'nullable|exists:product_combinations,id',
            
            // Legacy flat fabric fields
            'is_fabric_used' => 'nullable|boolean',
            'standard_fabric_width' => 'nullable|numeric|min:0.01',
            'standard_fabric_length' => 'nullable|numeric|min:0.01',
            'fabric_width_unit' => 'nullable|string',
            'fabric_length_unit' => 'nullable|string',
            'tasks' => 'nullable|array',
            'tasks.*.task_id' => 'required_with:tasks|exists:tasks,id',
            'tasks.*.sequence_number' => 'nullable|integer|min:1',
            'tasks.*.standard_labor_rate' => 'nullable|numeric|min:0',
            'tasks.*.is_final_step' => 'nullable|boolean',

            // Dynamic Patterns repeater
            'patterns' => 'nullable|array',
            'patterns.*.id' => 'nullable|integer',
            'patterns.*.name' => 'required_with:patterns|string|max:255',
            'patterns.*.fabric_width_id' => 'nullable|exists:fabric_widths,id',
            'patterns.*.fabric_length' => 'nullable|numeric|min:0.01',
            'patterns.*.fabric_length_unit' => 'nullable|string',
            'patterns.*.standard_labor_rate' => 'nullable|numeric|min:0',
            'patterns.*.widths' => 'nullable|array',
            'patterns.*.widths.*.fabric_width_id' => 'required_with:patterns.*.widths|exists:fabric_widths,id',
            'patterns.*.widths.*.fabric_length' => 'required_with:patterns.*.widths|numeric|min:0.01',
            'patterns.*.widths.*.fabric_length_unit' => 'nullable|string',
            'patterns.*.is_subsidiary_used' => 'nullable|boolean',
            'patterns.*.subsidiary_materials' => 'nullable|array',
            'patterns.*.subsidiary_materials.*.raw_material_id' => 'required_with:patterns.*.subsidiary_materials|exists:raw_materials,id',
            'patterns.*.subsidiary_materials.*.consumption_quantity' => 'required_with:patterns.*.subsidiary_materials|numeric|min:0.0001',
            'patterns.*.tasks' => 'nullable|array',
            'patterns.*.tasks.*.task_id' => 'required_with:patterns.*.tasks|exists:tasks,id',
            'patterns.*.tasks.*.sequence_number' => 'nullable|integer|min:1',
            'patterns.*.tasks.*.standard_labor_rate' => 'nullable|numeric|min:0',
            'patterns.*.tasks.*.is_final_step' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product Name is required.',
            'manufacturing_product_category_id.required' => 'Category is required.',
            'manufacturing_product_category_id.exists' => 'Selected category is invalid.',
        ];
    }
}
