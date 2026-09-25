<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLaborCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => (bool) $this->status,
            'status_label' => $this->status ? 'Active' : 'Inactive',
            'tasks_count' => $this->tasks_count ?? ($this->relationLoaded('tasks') ? $this->tasks->count() : 0),
            'labors_count' => $this->labors_count ?? ($this->relationLoaded('labors') ? $this->labors->count() : 0),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
