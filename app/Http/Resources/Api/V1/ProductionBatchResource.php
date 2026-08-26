<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $manufacturingProduct = $this->manufacturingProduct;
        $supervisor = $this->supervisor;
        $jobsCount = $this->jobs ? $this->jobs->count() : 0;
        $completedJobsCount = $this->jobs ? $this->jobs->where('status', 'completed')->count() : 0;

        return [
            'id' => $this->id,
            'batch_code' => $this->batch_code,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
            'planned_quantity' => (int) $this->planned_quantity,
            'completed_quantity' => (int) ($this->completed_quantity ?? 0),
            'batch_date' => $this->batch_date ? (is_string($this->batch_date) ? $this->batch_date : $this->batch_date->format('Y-m-d')) : null,
            'notes' => $this->notes,
            'manufacturing_product' => $manufacturingProduct ? [
                'id' => $manufacturingProduct->id,
                'product_code' => $manufacturingProduct->product_code,
                'title' => $manufacturingProduct->title,
                'dimensions' => "{$manufacturingProduct->length} x {$manufacturingProduct->width} {$manufacturingProduct->unit}",
            ] : null,
            'supervisor' => $supervisor ? [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'email' => $supervisor->email,
            ] : null,
            'progress' => [
                'total_jobs' => $jobsCount,
                'completed_jobs' => $completedJobsCount,
                'completion_percentage' => $jobsCount > 0 ? round(($completedJobsCount / $jobsCount) * 100, 1) : 0,
            ],
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
