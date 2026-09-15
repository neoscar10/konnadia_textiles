<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OverheadAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'] ?? null,
            'year' => $this->resource['year'],
            'month' => $this->resource['month'],
            'period_formatted' => $this->resource['period_formatted'],
            'production_value' => (float) $this->resource['production_value'],
            'stitching_material_total' => (float) $this->resource['stitching_material_total'],
            'salaried_staff_total' => (float) $this->resource['salaried_staff_total'],
            'other_overheads_total' => (float) $this->resource['other_overheads_total'],
            'total_overhead' => (float) $this->resource['total_overhead'],
            'overhead_percentage' => (float) $this->resource['overhead_percentage'],
            'status' => $this->resource['status'] ?? 'draft',
            'material_rows' => $this->resource['material_rows'] ?? [],
            'salaried_labors' => $this->resource['salaried_labors'] ?? [],
            'other_overhead_rows' => $this->resource['other_overhead_rows'] ?? [],
        ];
    }
}
