<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\ManufacturingProduct;
use App\Models\Unit;

class FabricCuttingAreaService
{
    /**
     * Convert value to the Base Unit quantity using database Unit ratio_to_base.
     */
    public static function convertToBaseUnit(float $value, Unit|string|int|null $unit, ?int $unitGroupId = null): float
    {
        if (empty($value) || $value <= 0) {
            return 0.0;
        }

        if (empty($unit)) {
            return $value;
        }

        $unitModel = UnitConversionService::resolveUnit($unit, $unitGroupId);
        if (!$unitModel) {
            return $value;
        }

        $ratio = (float) $unitModel->ratio_to_base;
        return $value * ($ratio > 0 ? $ratio : 1.0);
    }

    /**
     * Convert value from Base Unit to a target Unit's display quantity.
     */
    public static function convertFromBaseUnit(float $baseValue, Unit|string|int|null $unit, ?int $unitGroupId = null): float
    {
        if (empty($baseValue) || $baseValue <= 0) {
            return 0.0;
        }

        if (empty($unit)) {
            return $baseValue;
        }

        $unitModel = UnitConversionService::resolveUnit($unit, $unitGroupId);
        if (!$unitModel) {
            return $baseValue;
        }

        $ratio = (float) $unitModel->ratio_to_base;
        return $ratio > 0 ? ($baseValue / $ratio) : $baseValue;
    }

    /**
     * Calculate single product piece fabric area in Base Unit^2.
     */
    public static function calculateProductPieceArea(ManufacturingProduct $product, ?int $unitGroupId = null): float
    {
        $length = (float) ($product->standard_fabric_length ?: 0);
        $width = (float) ($product->standard_fabric_width ?: 0);

        if ($length <= 0 || $width <= 0) {
            return 0.0;
        }

        $lengthBase = self::convertToBaseUnit($length, $product->fabric_length_unit ?: 'Meters', $unitGroupId);
        $widthBase = self::convertToBaseUnit($width, $product->fabric_width_unit ?: 'Centimeters', $unitGroupId);

        return $lengthBase * $widthBase;
    }

    /**
     * Calculate Fabric Roll Cut Area in Base Unit^2.
     */
    public static function calculateCutArea(float $cutLength, RawMaterial $rawMaterial): float
    {
        if ($cutLength <= 0) {
            return 0.0;
        }

        $unitGroupId = $rawMaterial->unit_group_id;
        $cutLengthBase = self::convertToBaseUnit($cutLength, $rawMaterial->unitModel ?? $rawMaterial->unit, $unitGroupId);
        $widthBase = self::convertToBaseUnit((float) ($rawMaterial->standard_width ?: 0), $rawMaterial->width_unit ?: 'Centimeters', $unitGroupId);

        return $cutLengthBase * $widthBase;
    }

    /**
     * Compute full cutting area breakdown:
     * - Cut Area (Base Unit^2)
     * - Total Used Area (Base Unit^2)
     * - Remaining Area (Base Unit^2)
     * - Auto Calculated Wastage Length (in Raw Material display unit)
     * - Max allowed quantity per product
     */
    public static function computeCuttingBreakdown(float $cutLength, RawMaterial $rawMaterial, array $productOutputs): array
    {
        $unitGroupId = $rawMaterial->unit_group_id;
        $cutAreaBase = self::calculateCutArea($cutLength, $rawMaterial);
        
        $widthBase = self::convertToBaseUnit((float) ($rawMaterial->standard_width ?: 0), $rawMaterial->width_unit ?: 'Centimeters', $unitGroupId);

        $totalUsedAreaBase = 0.0;
        $productDetails = [];

        foreach ($productOutputs as $output) {
            $productId = $output['manufacturing_product_id'] ?? null;
            $qty = floatval($output['quantity'] ?? $output['planned_quantity'] ?? 0);

            if (!$productId) continue;

            $product = ManufacturingProduct::find($productId);
            if (!$product) continue;

            $pieceAreaBase = self::calculateProductPieceArea($product, $unitGroupId);
            $itemTotalUsedAreaBase = $pieceAreaBase * $qty;
            $totalUsedAreaBase += $itemTotalUsedAreaBase;

            $productDetails[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'piece_area_base' => $pieceAreaBase,
                'quantity' => $qty,
                'total_used_area_base' => $itemTotalUsedAreaBase,
            ];
        }

        $remainingAreaBase = max(0.0, $cutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($cutAreaBase + 0.0001);

        // Auto wastage length in Base Unit = Remaining Area / Width in Base Unit
        $wastageLengthBase = $widthBase > 0 ? ($remainingAreaBase / $widthBase) : 0.0;
        
        // Convert wastage length back to Raw Material display unit
        $wastageLengthDisplay = self::convertFromBaseUnit($wastageLengthBase, $rawMaterial->unitModel ?? $rawMaterial->unit, $unitGroupId);

        // Calculate max allowed quantity for each product
        $maxQuantities = [];
        foreach ($productDetails as $pId => $details) {
            $pieceArea = $details['piece_area_base'];
            if ($pieceArea > 0) {
                // Available area for this product if other products are kept fixed
                $availableAreaForThis = $remainingAreaBase + $details['total_used_area_base'];
                $maxQuantities[$pId] = max(0, (int) floor($availableAreaForThis / $pieceArea));
            } else {
                $maxQuantities[$pId] = 999999;
            }
        }

        $usagePercentage = $cutAreaBase > 0 ? round(($totalUsedAreaBase / $cutAreaBase) * 100, 1) : 0;

        return [
            'cut_area_base' => round($cutAreaBase, 4),
            'used_area_base' => round($totalUsedAreaBase, 4),
            'remaining_area_base' => round($remainingAreaBase, 4),
            'wastage_length' => round($wastageLengthDisplay, 2),
            'wastage_length_base' => round($wastageLengthBase, 4),
            'usage_percentage' => $usagePercentage,
            'is_over_capacity' => $isOverCapacity,
            'over_capacity_diff_base' => $isOverCapacity ? round($totalUsedAreaBase - $cutAreaBase, 4) : 0.0,
            'product_details' => $productDetails,
            'max_quantities' => $maxQuantities,
        ];
    }
}
