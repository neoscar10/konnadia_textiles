<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSpareProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $recordedQty = (int) $this->quantity;
        $usedQty = (int) $this->used_quantity;
        $availableQty = max(0, $recordedQty - $usedQty);

        return [
            'id' => $this->id,
            'design_id' => $this->design_id,
            'production_batch_id' => $this->production_batch_id,
            'production_job_id' => $this->production_job_id,
            'manufacturing_product_id' => $this->manufacturing_product_id,
            'quantity' => $recordedQty,
            'used_quantity' => $usedQty,
            'available_quantity' => $availableQty,
            'status' => $availableQty > 0 ? 'available' : 'fully_used',
            'notes' => $this->notes,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,

            'manufacturing_product' => $this->whenLoaded('manufacturingProduct', function () {
                return [
                    'id' => $this->manufacturingProduct->id,
                    'name' => $this->manufacturingProduct->name ?? $this->manufacturingProduct->title,
                    'code' => $this->manufacturingProduct->code ?? $this->manufacturingProduct->product_code,
                    'status' => $this->manufacturingProduct->status,
                ];
            }),

            'production_batch' => $this->whenLoaded('productionBatch', function () {
                return [
                    'id' => $this->productionBatch->id,
                    'batch_code' => $this->productionBatch->batch_code,
                    'status' => $this->productionBatch->status,
                    'batch_date' => $this->productionBatch->batch_date,
                ];
            }),

            'production_job' => $this->whenLoaded('productionJob', function () {
                return [
                    'id' => $this->productionJob->id,
                    'job_code' => $this->productionJob->job_code,
                    'status' => $this->productionJob->status,
                ];
            }),
        ];
    }
}
