<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminWastageLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $year   = $this->created_at?->format('Y') ?? now()->year;
        $id     = str_pad((string) $this->id, 4, '0', STR_PAD_LEFT);
        $wastageCode = "WST-{$year}-{$id}";

        // Source batch code — mirrors the web logic
        $sourceBatchCode = $this->productionJob?->production_batch_id
            ?: ($this->productionJob?->batch?->batch_code
            ?: $this->job_code
            ?: null);

        // Pattern name — mirrors the web fallback chain
        $patternName = $this->pattern?->name
            ?? $this->productionJob?->pattern?->name
            ?? $this->manufacturingProduct?->patterns?->first()?->name
            ?? 'Standard Pattern';

        // Wastage type label
        $rawType     = strtolower($this->wastage_type ?? 'scrap');
        $isDamage    = in_array($rawType, ['damage', 'damaged']);
        $typeLabel   = $isDamage ? 'Damaged (Resold)' : 'Scrap (Unusable)';
        $typeSlug    = $isDamage ? 'damaged' : 'scrap';

        return [
            'id'                       => $this->id,
            'wastage_code'             => $wastageCode,
            'job_code'                 => $this->job_code,
            'source_batch_code'        => $sourceBatchCode,
            'wastage_type'             => $typeSlug,
            'wastage_type_label'       => $typeLabel,
            'is_damage'                => $isDamage,
            'quantity_wasted'          => (float) $this->quantity_wasted,
            'formatted_quantity_wasted'=> number_format((float) $this->quantity_wasted, 0) . ' Pcs',
            'reason'                   => $this->reason,

            // Related: Production Job
            'production_job_id'        => $this->production_job_id,
            'production_job'           => $this->whenLoaded('productionJob', function () {
                return [
                    'id'       => $this->productionJob->id,
                    'job_code' => $this->productionJob->job_code,
                    'status'   => $this->productionJob->status,
                ];
            }),

            // Related: Manufacturing Product
            'manufacturing_product_id' => $this->manufacturing_product_id,
            'manufacturing_product'    => $this->whenLoaded('manufacturingProduct', function () {
                return [
                    'id'   => $this->manufacturingProduct->id,
                    'name' => $this->manufacturingProduct->name,
                    'code' => $this->manufacturingProduct->code,
                ];
            }),

            // Related: Pattern
            'pattern_id'               => $this->pattern_id,
            'pattern_name'             => $patternName,
            'pattern'                  => $this->whenLoaded('pattern', function () {
                return [
                    'id'   => $this->pattern->id,
                    'name' => $this->pattern->name,
                ];
            }),

            // Related: Task / Production Stage
            'task_id'                  => $this->task_id,
            'task'                     => $this->whenLoaded('task', function () {
                return [
                    'id'   => $this->task->id,
                    'name' => $this->task->name,
                    'code' => $this->task->code ?? null,
                ];
            }),
            'stage_lost'               => $this->task?->name ?? 'Production Stage',

            // Related: Inventory Bale Roll
            'inventory_bale_roll_id'   => $this->inventory_bale_roll_id,

            // Timestamps
            'logged_date'              => $this->created_at?->format('Y-m-d'),
            'logged_at'                => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
        ];
    }
}
