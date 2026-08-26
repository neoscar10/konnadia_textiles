<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batch = $this->batch;
        $task = $this->task;
        $mfgProduct = $this->manufacturingProduct;

        return [
            'id' => $this->id,
            'job_code' => $this->job_code,
            'batch_code' => $batch ? $batch->batch_code : null,
            'production_batch_id' => $this->production_batch_id,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
            'sequence_index' => (int) $this->sequence_index,
            'target_quantity' => (int) $this->target_quantity,
            'completed_quantity' => (int) $this->completed_quantity,
            'rejected_quantity' => (int) $this->rejected_quantity,
            'damaged_quantity' => (int) $this->damaged_quantity,
            'unconverted_quantity' => (int) ($this->unconverted_quantity ?? 0),
            'remaining_unconverted_quantity' => (int) ($this->remaining_unconverted_quantity ?? 0),
            'task' => $task ? [
                'id' => $task->id,
                'code' => $task->code,
                'name' => $task->name,
                'consumes_raw_material' => (bool) $task->consumes_raw_material,
            ] : null,
            'manufacturing_product' => $mfgProduct ? [
                'id' => $mfgProduct->id,
                'product_code' => $mfgProduct->product_code ?? $mfgProduct->code,
                'title' => $mfgProduct->title ?? $mfgProduct->name,
            ] : null,
            'started_at' => $this->started_at ? $this->started_at->toIso8601String() : null,
            'completed_at' => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
