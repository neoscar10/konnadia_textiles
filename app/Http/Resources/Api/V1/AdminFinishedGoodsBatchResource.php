<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Catalog\CategoryService;

class AdminFinishedGoodsBatchResource extends JsonResource
{
    /**
     * Whether to include full audit detail (items + packaging deductions).
     */
    private bool $withAudit;

    public function __construct($resource, bool $withAudit = false)
    {
        parent::__construct($resource);
        $this->withAudit = $withAudit;
    }

    public function toArray(Request $request): array
    {
        $fep = $this->frontEndProduct;

        // Resolve category path
        $categoryDisplayName = null;
        if ($fep) {
            $categoryDisplayName = $fep->category_display_name;
        }

        $data = [
            'id'                    => $this->id,
            'barcode'               => $this->barcode,
            'design_id'             => $this->design_id,
            'converted_qty'         => $this->converted_qty,
            'unit'                  => $this->unit,
            'unit_factor'           => $this->unit_factor,
            'converted_date'        => $this->converted_date
                ? $this->converted_date->toIso8601String()
                : $this->created_at?->toIso8601String(),
            'is_published'          => (bool) $this->is_published,
            'notes'                 => $this->notes,
            'costing_summary'       => $this->costing_summary,

            'front_end_product_id'  => $this->front_end_product_id,
            'front_end_product'     => $fep ? [
                'id'                  => $fep->id,
                'sku'                 => $fep->sku,
                'name'                => $fep->name,
                'category_id'         => $fep->category_id,
                'category_display_name' => $categoryDisplayName,
                'leaf_category_name'  => $fep->leaf_category_name,
            ] : null,

            'creator'               => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null,

            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];

        // Include full audit data if requested (for show/audit endpoints)
        if ($this->withAudit) {
            $data['items'] = $this->relationLoaded('items')
                ? $this->items->map(fn ($item) => [
                    'id'                      => $item->id,
                    'manufacturing_product_id'=> $item->manufacturing_product_id,
                    'manufacturing_product'   => $item->manufacturingProduct ? [
                        'id'   => $item->manufacturingProduct->id,
                        'name' => $item->manufacturingProduct->name,
                        'code' => $item->manufacturingProduct->code,
                    ] : null,
                    'pattern_id'              => $item->pattern_id,
                    'pattern'                 => $item->pattern ? [
                        'id'   => $item->pattern->id,
                        'name' => $item->pattern->name,
                    ] : null,
                    'production_batch_id'     => $item->production_batch_id,
                    'production_batch'        => $item->productionBatch ? [
                        'id'         => $item->productionBatch->id,
                        'batch_code' => $item->productionBatch->batch_code,
                    ] : null,
                    'production_job_id'       => $item->production_job_id,
                    'production_job'          => $item->productionJob ? [
                        'id'       => $item->productionJob->id,
                        'job_code' => $item->productionJob->job_code,
                    ] : null,
                    'quantity_used'           => $item->quantity_used,
                ])
                : [];

            $data['packaging_deductions'] = $this->relationLoaded('packagingDeductions')
                ? $this->packagingDeductions->map(fn ($pkg) => [
                    'id'               => $pkg->id,
                    'raw_material_id'  => $pkg->raw_material_id,
                    'raw_material'     => $pkg->rawMaterial ? [
                        'id'   => $pkg->rawMaterial->id,
                        'name' => $pkg->rawMaterial->name,
                        'code' => $pkg->rawMaterial->code,
                    ] : null,
                    'quantity_deducted'=> $pkg->quantity_deducted,
                ])
                : [];
        }

        return $data;
    }
}
