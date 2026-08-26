<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionLedgerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batch = isset($this->resource['batch']) ? $this->resource['batch'] : null;
        $batchId = $batch ? $batch->id : ($this->resource['batch_id'] ?? null);
        $batchCode = $batch ? $batch->batch_code : ($this->resource['batch_code'] ?? null);
        $mfgProduct = $batch && $batch->manufacturingProduct ? [
            'id' => $batch->manufacturingProduct->id,
            'product_code' => $batch->manufacturingProduct->product_code,
            'title' => $batch->manufacturingProduct->title,
        ] : ($this->resource['manufacturing_product'] ?? null);

        return [
            'batch_id' => $batchId,
            'batch_code' => $batchCode,
            'manufacturing_product' => $mfgProduct,
            'summary_kpis' => [
                'total_manufacturing_cost' => (float) ($this->resource['total_manufacturing_cost'] ?? 0),
                'total_material_cost' => (float) ($this->resource['total_material_cost'] ?? 0),
                'fabric_cost' => (float) ($this->resource['fabric_cost'] ?? 0),
                'subsidiary_cost' => (float) ($this->resource['subsidiary_cost'] ?? 0),
                'stitching_cost' => (float) ($this->resource['stitching_cost'] ?? 0),
                'packaging_cost' => (float) ($this->resource['packaging_cost'] ?? 0),
                'overhead_cost' => (float) ($this->resource['overhead_cost'] ?? 0),
                'total_labor_cost' => (float) ($this->resource['total_labor_cost'] ?? 0),
                'total_wastage_cost' => (float) ($this->resource['total_wastage_cost'] ?? 0),
                'total_finished_units' => (int) ($this->resource['finished_units'] ?? 0),
                'average_cost_per_unit' => (float) ($this->resource['average_cost_per_unit'] ?? 0),
            ],
            'labor_details' => $this->resource['labor_details'] ?? [],
            'wastage_details' => $this->resource['wastage_details'] ?? [],
        ];
    }
}
