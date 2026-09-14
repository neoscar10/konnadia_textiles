<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminManufacturingProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = null;
        if ($this->image_path) {
            $imageUrl = str_starts_with($this->image_path, 'http')
                ? $this->image_path
                : asset(Storage::url($this->image_path));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'status' => $this->status,
            'status_label' => ucfirst($this->status ?? 'active'),
            'standard_labor_rate' => (float) ($this->standard_labor_rate ?? 0.00),
            'image_path' => $this->image_path,
            'image_url' => $imageUrl,
            'manufacturing_product_category_id' => $this->manufacturing_product_category_id,
            'category' => $this->relationLoaded('category') && $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'status' => (bool) $this->category->status,
            ] : null,

            'is_fabric_used' => (bool) ($this->is_fabric_used ?? true),
            'standard_fabric_width' => $this->standard_fabric_width !== null ? (float) $this->standard_fabric_width : null,
            'standard_fabric_length' => $this->standard_fabric_length !== null ? (float) $this->standard_fabric_length : null,
            'fabric_width_unit' => $this->fabric_width_unit,
            'fabric_length_unit' => $this->fabric_length_unit,

            'is_common_subsidiary' => (bool) ($this->is_common_subsidiary ?? true),
            'is_subsidiary_used' => (bool) ($this->is_subsidiary_used ?? false),
            'is_stitching_used' => (bool) ($this->is_stitching_used ?? false),
            'is_packaging_used' => (bool) ($this->is_packaging_used ?? false),

            'subsidiary_materials' => $this->relationLoaded('subsidiaryMaterials') ? $this->subsidiaryMaterials->map(function ($mat) {
                return [
                    'id' => $mat->id,
                    'name' => $mat->name,
                    'code' => $mat->code,
                    'unit' => $mat->unit,
                    'consumption_quantity' => (float) ($mat->pivot->consumption_quantity ?? 0.00),
                ];
            }) : [],

            'stitching_materials' => $this->relationLoaded('stitchingMaterials') ? $this->stitchingMaterials->map(function ($mat) {
                return [
                    'id' => $mat->id,
                    'name' => $mat->name,
                    'code' => $mat->code,
                    'unit' => $mat->unit,
                ];
            }) : [],

            'tasks' => $this->relationLoaded('tasks') ? $this->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->name,
                    'code' => $task->code,
                    'sequence_number' => (int) ($task->pivot->sequence_number ?? 1),
                    'standard_labor_rate' => (float) ($task->pivot->standard_labor_rate ?? 0.00),
                    'is_final_step' => (bool) ($task->pivot->is_final_step ?? false),
                ];
            }) : [],

            'patterns' => $this->relationLoaded('patterns') ? $this->patterns->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'fabric_width_id' => $p->fabric_width_id,
                    'fabric_width_name' => $p->fabricWidth?->name,
                    'fabric_width_value' => $p->fabricWidth?->value !== null ? (float) $p->fabricWidth->value : null,
                    'fabric_width_unit' => $p->fabricWidth?->unit,
                    'fabric_length' => (float) ($p->fabric_length ?? 0.00),
                    'fabric_length_unit' => $p->fabric_length_unit ?? 'm',
                    'standard_labor_rate' => (float) ($p->standard_labor_rate ?? 0.00),
                    'is_default' => (bool) ($p->is_default ?? false),
                    'is_subsidiary_used' => (bool) ($p->is_subsidiary_used ?? false),

                    'widths' => $p->relationLoaded('patternFabricWidths') ? $p->patternFabricWidths->map(function ($pw) {
                        return [
                            'id' => $pw->id,
                            'fabric_width_id' => $pw->fabric_width_id,
                            'fabric_width_name' => $pw->fabricWidth?->name,
                            'fabric_width_value' => $pw->fabricWidth?->value !== null ? (float) $pw->fabricWidth->value : null,
                            'fabric_width_unit' => $pw->fabricWidth?->unit,
                            'fabric_length' => (float) ($pw->fabric_length ?? 0.00),
                            'fabric_length_unit' => $pw->fabric_length_unit ?? 'm',
                        ];
                    }) : [],

                    'subsidiary_materials' => $p->relationLoaded('subsidiaryMaterials') ? $p->subsidiaryMaterials->map(function ($sm) {
                        return [
                            'id' => $sm->id,
                            'name' => $sm->name,
                            'code' => $sm->code,
                            'unit' => $sm->unit,
                            'consumption_quantity' => (float) ($sm->pivot->consumption_quantity ?? 0.00),
                        ];
                    }) : [],

                    'tasks' => $p->relationLoaded('tasks') ? $p->tasks->map(function ($pt) {
                        return [
                            'id' => $pt->id,
                            'name' => $pt->name,
                            'code' => $pt->code,
                            'sequence_number' => (int) ($pt->pivot->sequence_number ?? 1),
                            'standard_labor_rate' => (float) ($pt->pivot->standard_labor_rate ?? 0.00),
                            'is_final_step' => (bool) ($pt->pivot->is_final_step ?? false),
                        ];
                    }) : [],
                ];
            }) : [],

            'storefront_product' => $this->relationLoaded('frontendProduct') && $this->frontendProduct ? [
                'id' => $this->frontendProduct->id,
                'title' => $this->frontendProduct->title,
                'sku' => $this->frontendProduct->sku,
            ] : null,

            'storefront_combination' => $this->relationLoaded('frontendCombination') && $this->frontendCombination ? [
                'id' => $this->frontendCombination->id,
                'sku' => $this->frontendCombination->sku,
                'title' => $this->frontendCombination->title ?? $this->frontendCombination->sku,
            ] : null,

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
