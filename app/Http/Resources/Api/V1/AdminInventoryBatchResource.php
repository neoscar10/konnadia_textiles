<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminInventoryBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $material = $this->rawMaterial;
        $category = $material ? $material->category : null;

        return [
            'id' => $this->id,
            'batch_number' => $this->batch_number,
            'raw_material_id' => $this->raw_material_id,
            'raw_material' => $material ? [
                'id' => $material->id,
                'name' => $material->name,
                'code' => $material->code,
                'category_name' => $category ? $category->name : null,
                'category_code' => $category ? $category->code : null,
                'unit' => $material->unit,
            ] : null,
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
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
