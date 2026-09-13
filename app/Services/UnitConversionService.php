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
            return self::fallbackConversion($quantity, $fromUnit, $toUnit);
        }

        // If from and to units belong to different unit groups, try direct ratio fallback
        if ($source->unit_group_id !== $target->unit_group_id) {
            return self::fallbackConversion($quantity, $fromUnit, $toUnit);
        }

        // Convert to Base Unit first, then from Base Unit to Target Unit
        $baseQuantity = $quantity * (float) $source->ratio_to_base;
        $targetRatio = (float) $target->ratio_to_base;

        return $targetRatio > 0 ? ($baseQuantity / $targetRatio) : $baseQuantity;
    }

    /**
     * Resolve a unit parameter into a Unit model with fuzzy alias support.
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
            $rawStr = trim($unit);
            $normalized = self::normalizeAlias($rawStr);

            $query = Unit::query();
            if ($unitGroupId) {
                $query->where('unit_group_id', $unitGroupId);
            }

            $matched = (clone $query)->where(function ($q) use ($rawStr, $normalized) {
                $q->where('short_code', 'like', $rawStr)
                  ->orWhere('name', 'like', $rawStr)
                  ->orWhere('short_code', 'like', $normalized)
                  ->orWhere('name', 'like', $normalized);
            })->first();

            if ($matched) {
                return $matched;
            }

            // Fallback global search without group scope if provided group didn't match
            if ($unitGroupId) {
                return Unit::where(function ($q) use ($rawStr, $normalized) {
                    $q->where('short_code', 'like', $rawStr)
                      ->orWhere('name', 'like', $rawStr)
                      ->orWhere('short_code', 'like', $normalized)
                      ->orWhere('name', 'like', $normalized);
                })->first();
            }
        }

        return null;
    }

    /**
     * Normalize unit string aliases (e.g., FIT/feet -> FT, Inches -> IN).
     */
    public static function normalizeAlias(string $unitStr): string
    {
        $u = strtolower(trim($unitStr));
        if ($u === 'fit' || str_contains($u, 'feet') || str_contains($u, 'foot') || $u === 'ft') {
            return 'FT';
        }
        if (str_contains($u, 'inch') || $u === 'in' || $u === '"') {
            return 'IN';
        }
        if (str_contains($u, 'cm') || str_contains($u, 'centimeter')) {
            return 'CM';
        }
        if (str_contains($u, 'meter') || $u === 'm') {
            return 'M';
        }
        if (str_contains($u, 'yard') || $u === 'yd') {
            return 'YD';
        }
        return strtoupper($unitStr);
    }

    /**
     * Fallback conversion for length units if database lookup fails.
     */
    protected static function fallbackConversion(float $quantity, Unit|string|int $from, Unit|string|int $to): float
    {
        $fromStr = is_string($from) ? $from : ($from instanceof Unit ? $from->short_code : '');
        $toStr = is_string($to) ? $to : ($to instanceof Unit ? $to->short_code : '');

        $fromAlias = self::normalizeAlias((string) $fromStr);
        $toAlias = self::normalizeAlias((string) $toStr);

        $ratiosInMeters = [
            'M'  => 1.0,
            'CM' => 0.01,
            'FT' => 0.3048,
            'IN' => 0.0254,
            'YD' => 0.9144,
        ];

        if (isset($ratiosInMeters[$fromAlias]) && isset($ratiosInMeters[$toAlias])) {
            $baseQty = $quantity * $ratiosInMeters[$fromAlias];
            return $baseQty / $ratiosInMeters[$toAlias];
        }

        return $quantity;
    }
}
