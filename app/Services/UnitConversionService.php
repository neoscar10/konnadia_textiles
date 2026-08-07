<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\UnitGroup;

class UnitConversionService
{
    /**
     * Convert quantity from source unit to target unit within a unit group.
     *
     * @param float $quantity
     * @param Unit|string|int $fromUnit (Unit instance, ID, or Unit Name/ShortCode)
     * @param Unit|string|int $toUnit (Unit instance, ID, or Unit Name/ShortCode)
     * @param int|null $unitGroupId Optional unit group ID scope for name resolution
     * @return float
     */
    public static function convert(float $quantity, Unit|string|int $fromUnit, Unit|string|int $toUnit, ?int $unitGroupId = null): float
    {
        $source = self::resolveUnit($fromUnit, $unitGroupId);
        $target = self::resolveUnit($toUnit, $unitGroupId);

        if (!$source || !$target) {
            return $quantity;
        }

        // If from and to units belong to different unit groups, conversion is direct fallback if ratios differ
        if ($source->unit_group_id !== $target->unit_group_id) {
            return $quantity;
        }

        // Convert to Base Unit first, then from Base Unit to Target Unit
        $baseQuantity = $quantity * (float) $source->ratio_to_base;
        $targetRatio = (float) $target->ratio_to_base;

        return $targetRatio > 0 ? ($baseQuantity / $targetRatio) : $baseQuantity;
    }

    /**
     * Resolve a unit parameter into a Unit model.
     */
    public static function resolveUnit(Unit|string|int $unit, ?int $unitGroupId = null): ?Unit
    {
        if ($unit instanceof Unit) {
            return $unit;
        }

        if (is_numeric($unit)) {
            return Unit::find((int) $unit);
        }

        if (is_string($unit) && !empty(trim($unit))) {
            $query = Unit::query();
            if ($unitGroupId) {
                $query->where('unit_group_id', $unitGroupId);
            }

            return $query->where(function ($q) use ($unit) {
                $q->where('name', $unit)->orWhere('short_code', $unit);
            })->first();
        }

        return null;
    }
}
