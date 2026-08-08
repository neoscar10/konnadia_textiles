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
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
