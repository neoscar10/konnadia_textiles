<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminTaskResource extends JsonResource
{
    /**
     * Transform the task resource into an array for Mobile App consumption.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => (bool) $this->status,
            'status_label' => $this->status ? 'Active' : 'Inactive',
            'sequence_number' => $this->sequence_number,
            'consumes_raw_material' => (bool) $this->consumes_raw_material,
            'is_labor_required' => (bool) $this->is_labor_required,
            'raw_material_categories' => $this->whenLoaded('rawMaterialCategories', function () {
                return $this->rawMaterialCategories->map(fn($cat) => [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'code' => $cat->code,
                    'unit_type' => $cat->unit_type?->value ?? (string) $cat->unit_type,
                ]);
            }),
            'raw_material_category_ids' => $this->whenLoaded('rawMaterialCategories', function () {
                return $this->rawMaterialCategories->pluck('id')->toArray();
            }),
            'manufacturing_products_count' => $this->when(
                isset($this->manufacturing_products_count),
                $this->manufacturing_products_count,
                fn() => $this->manufacturingProducts()->count()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
