<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminFabricWidthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unitCode = $this->unitModel ? $this->unitModel->short_code : ($this->unit ?: 'IN');
        $display = $this->name ? "{$this->name} ({$this->value}{$unitCode})" : "{$this->value}{$unitCode}";

        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => (float) $this->value,
            'unit_id' => $this->unit_id,
            'unit' => $unitCode,
            'unit_model' => $this->whenLoaded('unitModel', function () {
                return $this->unitModel ? [
                    'id' => $this->unitModel->id,
                    'name' => $this->unitModel->name,
                    'short_code' => $this->unitModel->short_code,
                ] : null;
            }),
            'display_label' => $display,
            'status' => (bool) $this->status,
            'is_in_use' => $this->isInUse(),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
