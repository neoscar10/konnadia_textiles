<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSupervisorResource extends JsonResource
{
    /**
     * Transform the factory supervisor resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'department' => $this->department,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'status_label' => $this->is_active ? 'Active' : 'Inactive',
            'production_batches_count' => $this->when(
                isset($this->production_batches_count),
                (int) $this->production_batches_count,
                fn() => $this->productionBatches()->count()
            ),
            'recent_batches' => $this->whenLoaded('productionBatches', function () {
                return $this->productionBatches->take(10)->map(function ($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'status' => $batch->status,
                        'target_quantity' => (float) $batch->target_quantity,
                        'manufacturing_product' => $batch->manufacturingProduct ? [
                            'id' => $batch->manufacturingProduct->id,
                            'name' => $batch->manufacturingProduct->name,
                            'code' => $batch->manufacturingProduct->code,
                        ] : null,
                        'created_at' => $batch->created_at?->toIso8601String(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
