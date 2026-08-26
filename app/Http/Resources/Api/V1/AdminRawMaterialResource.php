<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRawMaterialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $category = $this->category;
        $unitGroup = $this->unitGroup;
        $unitModel = $this->unitModel;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'raw_material_category_id' => $this->raw_material_category_id,
            'category' => $category ? [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'unit_type' => is_object($category->unit_type) ? $category->unit_type->value : $category->unit_type,
            ] : null,
            'unit' => $this->unit,
            'unit_id' => $this->unit_id,
            'unit_group_id' => $this->unit_group_id,
            'unit_group' => $unitGroup ? [
                'id' => $unitGroup->id,
                'name' => $unitGroup->name,
            ] : null,
            'unit_model' => $unitModel ? [
                'id' => $unitModel->id,
                'name' => $unitModel->name,
                'short_code' => $unitModel->short_code,
                'is_base' => (bool) $unitModel->is_base,
                'ratio_to_base' => (float) $unitModel->ratio_to_base,
            ] : null,
            'standard_width' => (float) $this->standard_width,
            'width_unit' => $this->width_unit,
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Active' : 'Inactive',
            'current_balance_quantity' => (float) ($this->batches()->where('status', 'active')->sum('balance_quantity') ?? 0),
            'linked_batches_count' => (int) $this->batches()->count(),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
