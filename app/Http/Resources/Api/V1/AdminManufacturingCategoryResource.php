<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminManufacturingCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productsCount = $this->manufacturing_products_count ?? $this->manufacturingProducts()->count();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => (bool) $this->status,
            'status_label' => $this->status ? 'Active' : 'Inactive',
            'manufacturing_products_count' => (int) $productsCount,
            'default_tasks' => $this->relationLoaded('defaultTasks') ? $this->defaultTasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'code' => $task->code,
                    'sequence_number' => (int) ($task->pivot->sequence_number ?? 1),
                    'standard_labor_rate' => $task->pivot->standard_labor_rate !== null ? (float) $task->pivot->standard_labor_rate : null,
                    'is_final_step' => (bool) ($task->pivot->is_final_step ?? false),
                ];
            }) : [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
