<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'discount_percentage' => (float) $this->discount_percentage,
            'default_credit_limit' => (float) $this->default_credit_limit,
            'sort_order' => (int) $this->sort_order,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'customers_count' => $this->whenCounted('customers', $this->customers_count ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
