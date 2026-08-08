<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUnitRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $group = $this->unitGroup;
        $baseUnit = $group ? $group->units->firstWhere('is_base', true) : null;
        
        $ratio = (float) $this->ratio_to_base > 0 ? (float) $this->ratio_to_base : 1.0;
        $formattedRatio = (floor($ratio) == $ratio) ? number_format($ratio, 0) : rtrim(rtrim(number_format($ratio, 6), '0'), '.');
        
        $relationshipStatement = null;
        $explanation = null;

        if ($this->is_base) {
            $relationshipStatement = "1 {$this->name} ({$this->short_code}) is the Base Unit for {$group?->name}";
            $explanation = "All other units in this group calculate quantities relative to 1 {$this->short_code}";
        } elseif ($baseUnit) {
            $relationshipStatement = "1 {$this->name} ({$this->short_code}) = {$formattedRatio} {$baseUnit->name} ({$baseUnit->short_code})";
            if ($ratio < 1 && $ratio > 0) {
                $reciprocal = 1 / $ratio;
                $formattedReciprocal = (floor($reciprocal) == $reciprocal) ? number_format($reciprocal, 0) : rtrim(rtrim(number_format($reciprocal, 6), '0'), '.');
                $explanation = "1 {$baseUnit->name} ({$baseUnit->short_code}) = {$formattedReciprocal} {$this->name} ({$this->short_code})";
            } else {
                $explanation = "Every 1 {$this->name} ({$this->short_code}) used in manufacturing or stock counts as {$formattedRatio} {$baseUnit->name} ({$baseUnit->short_code})";
            }
        }

        return [
            'id' => $this->id,
            'unit_group_id' => $this->unit_group_id,
            'unit_group' => $group ? [
                'id' => $group->id,
                'name' => $group->name,
                'code' => $group->code,
            ] : null,
            'name' => $this->name,
            'short_code' => $this->short_code,
            'is_base' => (bool) $this->is_base,
            'ratio_to_base' => (float) $this->ratio_to_base,
            'is_active' => (bool) $this->is_active,
            'relationship_statement' => $relationshipStatement,
            'explanation' => $explanation,
            'base_unit' => $baseUnit ? [
                'id' => $baseUnit->id,
                'name' => $baseUnit->name,
                'short_code' => $baseUnit->short_code,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
