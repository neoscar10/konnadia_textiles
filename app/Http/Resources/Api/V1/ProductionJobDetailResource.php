<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionJobDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batch = $this->batch;
        $task = $this->task;
        $mfgProduct = $this->manufacturingProduct;

        // Allocations (Assigned Laborers)
        $allocations = $this->laborAllocations->map(function ($alloc) {
            return [
                'id' => $alloc->id,
                'labor_id' => $alloc->labor_id,
                'labor_name' => $alloc->labor ? $alloc->labor->name : 'Unknown Laborer',
                'labor_code' => $alloc->labor ? $alloc->labor->labor_code : null,
                'rate_per_piece' => (float) $alloc->rate_per_piece,
                'assigned_quantity' => (int) $alloc->assigned_quantity,
                'completed_quantity' => (int) $alloc->completed_quantity,
                'total_payout' => (float) $alloc->total_payout,
            ];
        });

        // Material Consumptions
        $consumptions = $this->materialConsumptions->map(function ($cons) {
            $invBatch = $cons->inventoryBatch;
            return [
                'id' => $cons->id,
                'inventory_batch_id' => $cons->inventory_batch_id,
                'batch_number' => $invBatch ? $invBatch->batch_number : null,
                'raw_material_title' => $invBatch && $invBatch->rawMaterial ? $invBatch->rawMaterial->name : null,
                'quantity_consumed' => (float) $cons->quantity_consumed,
                'wastage_quantity' => (float) $cons->wastage_quantity,
                'unit' => $cons->unit,
                'unit_cost' => (float) $cons->unit_cost,
                'total_cost' => (float) $cons->total_cost,
            ];
        });

        // Outputs
        $outputs = $this->outputs->map(function ($out) {
            return [
                'id' => $out->id,
                'completed_quantity' => (int) $out->completed_quantity,
                'rejected_quantity' => (int) $out->rejected_quantity,
                'damaged_quantity' => (int) $out->damaged_quantity,
                'recorded_by' => $out->recordedBy ? $out->recordedBy->name : 'System',
                'recorded_at' => $out->created_at ? $out->created_at->toIso8601String() : null,
                'notes' => $out->notes,
            ];
        });

        // Alterations
        $alterations = $this->alterations->map(function ($alt) {
            $targetProduct = $alt->targetManufacturingProduct;
            return [
                'id' => $alt->id,
                'target_manufacturing_product' => $targetProduct ? [
                    'id' => $targetProduct->id,
                    'product_code' => $targetProduct->product_code,
                    'title' => $targetProduct->title,
                ] : null,
                'quantity' => (int) $alt->quantity,
                'reason' => $alt->reason,
                'created_at' => $alt->created_at ? $alt->created_at->toIso8601String() : null,
            ];
        });

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
            'notes' => $this->notes,
            'task' => $task ? [
                'id' => $task->id,
                'code' => $task->code,
                'name' => $task->name,
                'consumes_raw_material' => (bool) $task->consumes_raw_material,
                'default_rate_per_piece' => (float) $task->default_rate_per_piece,
            ] : null,
            'manufacturing_product' => $mfgProduct ? [
                'id' => $mfgProduct->id,
                'product_code' => $mfgProduct->product_code ?? $mfgProduct->code,
                'title' => $mfgProduct->title ?? $mfgProduct->name,
                'length' => (float) $mfgProduct->length,
                'width' => (float) $mfgProduct->width,
                'unit' => $mfgProduct->unit,
            ] : null,
            'labor_allocations' => $allocations,
            'material_consumptions' => $consumptions,
            'outputs' => $outputs,
            'alterations' => $alterations,
            'started_at' => $this->started_at ? $this->started_at->toIso8601String() : null,
            'completed_at' => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
