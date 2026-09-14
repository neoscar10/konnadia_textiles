<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRawMaterialCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'unit_type' => is_object($this->unit_type) ? $this->unit_type->value : ($this->unit_type ?? 'unit_based'),
            'unit_group' => $this->whenLoaded('unitGroup', function () {
                return $this->unitGroup ? [
                    'id' => $this->unitGroup->id,
                    'name' => $this->unitGroup->name,
                    'code' => $this->unitGroup->code,
                ] : null;
            }),
            'description' => $this->description,
            'status' => (bool) ($this->is_active ?? true),
            'is_active' => (bool) ($this->is_active ?? true),
            'raw_materials_count' => $this->whenCounted('materials', $this->materials_count, $this->whenCounted('rawMaterials')),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
