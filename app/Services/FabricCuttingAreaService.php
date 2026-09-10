<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\ManufacturingProduct;
use App\Models\ManufacturingProductPattern;
use App\Models\ManufacturingPatternFabricWidth;
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
     * Resolve standard fabric length consumption per piece for a specific product/pattern at a given roll fabric width.
     */
    public static function resolvePatternFabricLength(ManufacturingProduct $product, RawMaterial $rawMaterial, ?int $patternId = null): float
    {
        $pattern = null;
        if ($patternId) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth')->find($patternId);
        }
        if (!$pattern) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth')
                ->where('manufacturing_product_id', $product->id)
                ->where('is_default', true)
                ->first();
        }
        if (!$pattern) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth')
                ->where('manufacturing_product_id', $product->id)
                ->first();
        }

        if ($pattern && $pattern->patternFabricWidths->isNotEmpty()) {
            $rawWidthVal = (float) ($rawMaterial->standard_width ?: 0);
            
            foreach ($pattern->patternFabricWidths as $pfw) {
                $pfwWidthVal = (float) ($pfw->fabricWidth?->width_inches ?: $pfw->fabricWidth?->width ?? 0);
                if (abs($pfwWidthVal - $rawWidthVal) < 0.5 || ($pfw->fabric_width_id && $pfw->fabric_width_id === $rawMaterial->fabric_width_id)) {
                    return (float) $pfw->fabric_length;
                }
            }
        }

        if ($pattern && (float) $pattern->fabric_length > 0) {
            return (float) $pattern->fabric_length;
        }

        return (float) ($product->standard_fabric_length ?: 2.5);
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
     * Compute full cutting area breakdown & area-weighted wastage cost allocation:
     * - Cut Area & Required Standard Length
     * - Total Used Area (Base Unit^2)
     * - Cutting Wastage Length & Cost
     * - Area-Weighted Cost Allocation per Product (so larger area products bear higher waste cost)
     */
    public static function computeCuttingBreakdown(float $cutLength, RawMaterial $rawMaterial, array $productOutputs, float $purchaseRate = 0.0): array
    {
        $unitGroupId = $rawMaterial->unit_group_id;
        $cutAreaBase = self::calculateCutArea($cutLength, $rawMaterial);
        
        $widthBase = self::convertToBaseUnit((float) ($rawMaterial->standard_width ?: 0), $rawMaterial->width_unit ?: 'Centimeters', $unitGroupId);

        $totalUsedAreaBase = 0.0;
        $totalStandardRequiredLength = 0.0;
        $productDetails = [];

        foreach ($productOutputs as $output) {
            $productId = $output['manufacturing_product_id'] ?? null;
            $qty = floatval($output['quantity'] ?? $output['planned_quantity'] ?? 0);
            $patternId = $output['pattern_id'] ?? null;

            if (!$productId || $qty <= 0) continue;

            $product = ManufacturingProduct::find($productId);
            if (!$product) continue;

            $pieceAreaBase = self::calculateProductPieceArea($product, $unitGroupId);
            $itemTotalUsedAreaBase = $pieceAreaBase * $qty;
            $totalUsedAreaBase += $itemTotalUsedAreaBase;

            $pieceReqLength = self::resolvePatternFabricLength($product, $rawMaterial, $patternId);
            $itemReqLength = $pieceReqLength * $qty;
            $totalStandardRequiredLength += $itemReqLength;

            $productDetails[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'piece_area_base' => $pieceAreaBase,
                'quantity' => $qty,
                'piece_req_length' => $pieceReqLength,
                'total_req_length' => $itemReqLength,
                'total_used_area_base' => $itemTotalUsedAreaBase,
            ];
        }

        $remainingAreaBase = max(0.0, $cutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($cutAreaBase + 0.0001);

        // Wastage length based on pattern standard requirement (or area remaining)
        $patternWastageLength = max(0.0, $cutLength - $totalStandardRequiredLength);

        // Auto wastage length in Base Unit = Remaining Area / Width in Base Unit
        $wastageLengthBase = $widthBase > 0 ? ($remainingAreaBase / $widthBase) : 0.0;
        $wastageLengthDisplay = self::convertFromBaseUnit($wastageLengthBase, $rawMaterial->unitModel ?? $rawMaterial->unit, $unitGroupId);

        // Effective wastage length is max of area remaining display and pattern wastage length
        $effectiveWastageLength = max($wastageLengthDisplay, $patternWastageLength);
        $totalWastageCost = round($effectiveWastageLength * $purchaseRate, 2);

        // Area-Weighted Cost Allocation across produced items
        $totalFabricCutCost = round($cutLength * $purchaseRate, 2);
        foreach ($productDetails as $pId => &$det) {
            $areaRatio = $totalUsedAreaBase > 0 ? ($det['total_used_area_base'] / $totalUsedAreaBase) : (1 / count($productDetails));
            
            // Base required cost for this product's cut
            $det['base_cost'] = round($det['total_req_length'] * $purchaseRate, 2);

            // Area-weighted allocated wastage cost
            $det['allocated_wastage_cost'] = round($totalWastageCost * $areaRatio, 2);

            // Total fabric cost for this product's output
            $det['total_fabric_cost'] = round($det['base_cost'] + $det['allocated_wastage_cost'], 2);
            $det['cost_per_piece'] = $det['quantity'] > 0 ? round($det['total_fabric_cost'] / $det['quantity'], 2) : 0.0;
            $det['wastage_per_piece'] = $det['quantity'] > 0 ? round($det['allocated_wastage_cost'] / $det['quantity'], 2) : 0.0;
        }
        unset($det);

        // Calculate max allowed quantity for each product
        $maxQuantities = [];
        foreach ($productDetails as $pId => $details) {
            $pieceArea = $details['piece_area_base'];
            if ($pieceArea > 0) {
                $otherProductsUsedArea = $totalUsedAreaBase - $details['total_used_area_base'];
                $availableAreaForThis = max(0.0, $cutAreaBase - $otherProductsUsedArea);
                $maxQuantities[$pId] = max(0, (int) floor($availableAreaForThis / $pieceArea));
            } else {
                $maxQuantities[$pId] = 999999;
            }
        }

        $usagePercentage = $cutAreaBase > 0 ? round(($totalUsedAreaBase / $cutAreaBase) * 100, 1) : 0;

        return [
            'cut_length' => round($cutLength, 2),
            'standard_required_length' => round($totalStandardRequiredLength, 2),
            'cut_area_base' => round($cutAreaBase, 4),
            'used_area_base' => round($totalUsedAreaBase, 4),
            'remaining_area_base' => round($remainingAreaBase, 4),
            'wastage_length' => round($effectiveWastageLength, 2),
            'wastage_length_base' => round($wastageLengthBase, 4),
            'total_wastage_cost' => $totalWastageCost,
            'total_fabric_cut_cost' => $totalFabricCutCost,
            'usage_percentage' => $usagePercentage,
            'is_over_capacity' => $isOverCapacity,
            'over_capacity_diff_base' => $isOverCapacity ? round($totalUsedAreaBase - $cutAreaBase, 4) : 0.0,
            'product_details' => $productDetails,
            'max_quantities' => $maxQuantities,
        ];
    }
}
