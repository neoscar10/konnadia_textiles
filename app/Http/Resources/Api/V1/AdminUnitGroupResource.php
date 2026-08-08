<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUnitGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseUnit = $this->units ? $this->units->firstWhere('is_base', true) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'units_count' => $this->units ? $this->units->count() : 0,
            'base_unit' => $baseUnit ? [
                'id' => $baseUnit->id,
                'name' => $baseUnit->name,
                'short_code' => $baseUnit->short_code,
            ] : null,
            'units' => $this->whenLoaded('units', function () {
                return AdminUnitRecordResource::collection($this->units);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
