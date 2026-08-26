<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInventoryBatchDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $material = $this->rawMaterial;

        // Formatted Bales
        $bales = $this->bales->map(function ($bale) {
            return [
                'id' => $bale->id,
                'bale_number' => $bale->bale_number,
                'declared_length' => (float) $bale->declared_length,
                'is_opened' => (bool) $bale->is_opened,
                'opened_at' => $bale->opened_at ? $bale->opened_at->toIso8601String() : null,
                'roll_count' => (int) ($bale->roll_count ?? 0),
                'total_recorded_length' => (float) ($bale->total_recorded_length ?? 0),
                'rolls' => $bale->rolls->map(fn($r) => [
                    'id' => $r->id,
                    'roll_number' => $r->roll_number,
                    'initial_length' => (float) $r->initial_length,
                    'remaining_length' => (float) $r->remaining_length,
                    'status' => $r->status,
                ]),
            ];
        });

        // Formatted Consumptions
        $consumptions = $this->consumptions->map(function ($cons) {
            $job = $cons->job;
            $mfgProd = $job ? $job->manufacturingProduct : null;
            return [
                'id' => $cons->id,
                'job_code' => $cons->job_code,
                'manufacturing_product_title' => $mfgProd ? $mfgProd->title : null,
                'quantity_consumed' => (float) $cons->quantity_consumed,
                'wastage_quantity' => (float) $cons->wastage_quantity,
                'unit' => $cons->unit,
                'total_cost' => (float) $cons->total_cost,
                'created_at' => $cons->created_at ? $cons->created_at->toIso8601String() : null,
            ];
        });

        // Formatted Audit Logs
        $logs = $this->logs->map(function ($log) {
            return [
                'id' => $log->id,
                'action_type' => $log->action_type,
                'quantity' => (float) $log->quantity,
                'notes' => $log->notes,
                'user_name' => $log->user ? $log->user->name : 'System',
                'created_at' => $log->created_at ? $log->created_at->toIso8601String() : null,
            ];
        });

        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'raw_material' => $material ? new AdminRawMaterialResource($material) : null,
            'supplier_name' => $this->supplier_name,
            'purchase_date' => $this->purchase_date ? (is_string($this->purchase_date) ? $this->purchase_date : $this->purchase_date->format('Y-m-d')) : null,
            'invoice_number' => $this->invoice_number,
            'quantity_received' => (float) $this->quantity_received,
            'balance_quantity' => (float) $this->balance_quantity,
            'quantity_consumed' => (float) $this->quantity_consumed,
            'unit' => $this->unit,
            'purchase_rate' => (float) $this->purchase_rate,
            'total_amount' => (float) $this->total_amount,
            'num_bales' => (int) ($this->num_bales ?? 0),
            'declared_bale_length' => (float) ($this->declared_bale_length ?? 0),
            'status' => $this->status,
            'status_label' => ucfirst($this->status),
            'bales' => $bales,
            'consumptions' => $consumptions,
            'logs' => $logs,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
