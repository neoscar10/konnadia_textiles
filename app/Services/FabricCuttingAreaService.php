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
     * Calculate product/pattern surface area in square meters (m^2).
     */
    public static function calculateProductPatternAreaM2(?ManufacturingProduct $product, ?ManufacturingProductPattern $pattern = null): float
    {
        if (!$product && !$pattern) {
            return 0.0;
        }

        if ($pattern && !$product) {
            $product = $pattern->manufacturingProduct;
        }

        if (!$pattern && $product) {
            $pattern = $product->defaultPattern ?? $product->patterns()->first();
        }

        $length = 0.0;
        $lengthUnit = 'Meters';

        $width = 0.0;
        $widthUnit = 'Centimeters';

        if ($pattern) {
            if ($pattern->relationLoaded('patternFabricWidths') ? $pattern->patternFabricWidths->isNotEmpty() : $pattern->patternFabricWidths()->exists()) {
                $pfw = $pattern->patternFabricWidths->first() ?? $pattern->patternFabricWidths()->first();
                if ($pfw) {
                    $length = (float) ($pfw->fabric_length ?: $pattern->fabric_length ?: 0);
                    $lengthUnit = $pattern->fabric_length_unit ?: ($product?->fabric_length_unit ?: 'Meters');

                    $fw = $pfw->fabricWidth;
                    if ($fw) {
                        $width = (float) ($fw->width_inches ?: $fw->value ?: $fw->width ?: 0);
                        $widthUnit = $fw->unit ?: 'Inches';
                    }
                }
            }

            if ($length <= 0) {
                $length = (float) ($pattern->fabric_length ?: 0);
                $lengthUnit = $pattern->fabric_length_unit ?: ($product?->fabric_length_unit ?: 'Meters');
            }

            if ($width <= 0 && $pattern->fabricWidth) {
                $fw = $pattern->fabricWidth;
                $width = (float) ($fw->width_inches ?: $fw->value ?: $fw->width ?: 0);
                $widthUnit = $fw->unit ?: 'Inches';
            }
        }

        if ($length <= 0 && $product) {
            $length = (float) ($product->standard_fabric_length ?: 0);
            $lengthUnit = $product->fabric_length_unit ?: 'Meters';
        }

        if ($width <= 0 && $product) {
            $width = (float) ($product->standard_fabric_width ?: 0);
            $widthUnit = $product->fabric_width_unit ?: 'Centimeters';
        }

        if ($length <= 0 || $width <= 0) {
            return 0.0;
        }

        $lengthMeters = self::convertToMeters($length, $lengthUnit);
        $widthMeters = self::convertToMeters($width, $widthUnit);

        return round($lengthMeters * $widthMeters, 4);
    }

    /**
     * Format dimensions (Length and Width) of a Product/Pattern into clean multi-unit conversion strings.
     */
    public static function formatProductPatternDimensions(?ManufacturingProduct $product, ?ManufacturingProductPattern $pattern = null): array
    {
        if (!$product && !$pattern) {
            return [
                'length_val' => 0.0,
                'length_unit' => 'M',
                'length_display' => '—',
                'width_val' => 0.0,
                'width_unit' => 'IN',
                'width_display' => '—',
                'dimensions_display' => 'Length: — · Width: —',
                'piece_area_m2' => 0.0,
                'area_display' => '0 m²',
            ];
        }

        if ($pattern && !$product) {
            $product = $pattern->manufacturingProduct;
        }

        if (!$pattern && $product) {
            $pattern = $product->defaultPattern ?? $product->patterns()->first();
        }

        $length = 0.0;
        $lengthUnit = 'Meters';

        $width = 0.0;
        $widthUnit = 'Inches';

        if ($pattern) {
            if ($pattern->relationLoaded('patternFabricWidths') ? $pattern->patternFabricWidths->isNotEmpty() : $pattern->patternFabricWidths()->exists()) {
                $pfw = $pattern->patternFabricWidths->first() ?? $pattern->patternFabricWidths()->first();
                if ($pfw) {
                    $length = (float) ($pfw->fabric_length ?: $pattern->fabric_length ?: 0);
                    $lengthUnit = $pfw->fabric_length_unit ?: ($pattern->fabric_length_unit ?: ($product?->fabric_length_unit ?: 'Meters'));

                    $fw = $pfw->fabricWidth;
                    if ($fw) {
                        $width = (float) ($fw->value ?: $fw->width_inches ?: $fw->width ?: 0);
                        $widthUnit = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'Inches');
                    }
                }
            }

            if ($length <= 0) {
                $length = (float) ($pattern->fabric_length ?: 0);
                $lengthUnit = $pattern->fabric_length_unit ?: ($product?->fabric_length_unit ?: 'Meters');
            }

            if ($width <= 0 && $pattern->fabricWidth) {
                $fw = $pattern->fabricWidth;
                $width = (float) ($fw->value ?: $fw->width_inches ?: $fw->width ?: 0);
                $widthUnit = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'Inches');
            }
        }

        if ($length <= 0 && $product) {
            $length = (float) ($product->standard_fabric_length ?: 0);
            $lengthUnit = $product->fabric_length_unit ?: 'Meters';
        }

        if ($width <= 0 && $product) {
            $width = (float) ($product->standard_fabric_width ?: 0);
            $widthUnit = $product->fabric_width_unit ?: 'Inches';
        }

        // Convert Length
        $lengthMeters = self::convertToMeters($length, $lengthUnit);
        $lengthCm = UnitConversionService::convert($length, $lengthUnit, 'CM');
        $lengthInches = UnitConversionService::convert($length, $lengthUnit, 'IN');
        $lengthYards = UnitConversionService::convert($length, $lengthUnit, 'YD');

        $normLenUnit = UnitConversionService::normalizeAlias($lengthUnit);
        if ($normLenUnit === 'M') {
            $lengthDisplay = round($length, 2) . ' m (' . round($lengthCm, 1) . ' cm / ' . round($lengthInches, 2) . '" / ' . round($lengthYards, 2) . ' yd)';
        } elseif ($normLenUnit === 'IN') {
            $lengthDisplay = round($length, 2) . '" (' . round($lengthCm, 1) . ' cm / ' . round($lengthMeters, 2) . ' m / ' . round($lengthYards, 2) . ' yd)';
        } elseif ($normLenUnit === 'CM') {
            $lengthDisplay = round($length, 1) . ' cm (' . round($lengthMeters, 2) . ' m / ' . round($lengthInches, 2) . '" / ' . round($lengthYards, 2) . ' yd)';
        } elseif ($normLenUnit === 'YD') {
            $lengthDisplay = round($length, 2) . ' YD (' . round($lengthMeters, 2) . ' m / ' . round($lengthCm, 1) . ' cm / ' . round($lengthInches, 2) . '")';
        } elseif ($normLenUnit === 'FT') {
            $lengthDisplay = round($length, 2) . ' FT (' . round($lengthMeters, 2) . ' m / ' . round($lengthCm, 1) . ' cm)';
        } else {
            $lengthDisplay = round($length, 2) . ' ' . $lengthUnit . ' (' . round($lengthCm, 1) . ' cm)';
        }

        // Convert Width
        $widthMeters = self::convertToMeters($width, $widthUnit);
        $widthCm = UnitConversionService::convert($width, $widthUnit, 'CM');
        $widthInches = UnitConversionService::convert($width, $widthUnit, 'IN');

        $normWidthUnit = UnitConversionService::normalizeAlias($widthUnit);
        if ($normWidthUnit === 'IN') {
            $widthDisplay = round($widthInches, 2) . '" (' . round($widthCm, 1) . ' cm / ' . round($widthMeters, 2) . ' m)';
        } elseif ($normWidthUnit === 'CM') {
            $widthDisplay = round($widthCm, 1) . ' cm (' . round($widthInches, 2) . '" / ' . round($widthMeters, 2) . ' m)';
        } elseif ($normWidthUnit === 'M') {
            $widthDisplay = round($widthMeters, 2) . ' m (' . round($widthCm, 1) . ' cm / ' . round($widthInches, 2) . '")';
        } elseif ($normWidthUnit === 'YD') {
            $widthDisplay = round($width, 2) . ' YD (' . round($widthMeters, 2) . ' m / ' . round($widthCm, 1) . ' cm)';
        } elseif ($normWidthUnit === 'FT') {
            $widthDisplay = round($width, 2) . ' FT (' . round($widthMeters, 2) . ' m / ' . round($widthCm, 1) . ' cm)';
        } else {
            $widthDisplay = round($width, 2) . ' ' . $widthUnit . ' (' . round($widthCm, 1) . ' cm)';
        }

        $pieceAreaM2 = round($lengthMeters * $widthMeters, 4);
        $dimensionsDisplay = "Length: {$lengthDisplay} · Width: {$widthDisplay}";

        return [
            'length_val' => (float) $length,
            'length_unit' => $lengthUnit,
            'length_display' => $lengthDisplay,
            'width_val' => (float) $width,
            'width_unit' => $widthUnit,
            'width_display' => $widthDisplay,
            'dimensions_display' => $dimensionsDisplay,
            'piece_area_m2' => $pieceAreaM2,
            'area_display' => "{$pieceAreaM2} m²",
        ];
    }

    /**
     * Helper to convert length/width value to meters.
     */
    /**
     * Helper to convert length/width value to meters using database-driven unit conversion engine.
     */
    public static function convertToMeters(float $val, ?string $unitStr): float
    {
        if ($val <= 0) return 0.0;
        if (empty(trim($unitStr ?? ''))) return $val;

        return UnitConversionService::convert($val, $unitStr, 'M');
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
            'total_cut_length' => round($cutLength, 2),
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

    /**
     * Calculate live roll cut breakdown & yield preview as user enters cut length:
     * - Intelligently handles unit conversions between Inches, CM, Meters, Yards, Feet
     * - Calculates Cut Fabric Area (m^2 and sq in)
     * - Resolves piece fabric length requirement based on roll width
     * - Calculates estimated piece yield
     * - Computes target requirement, surplus/wastage length, area & cost
     */
    public static function calculateLiveRollCutBreakdown(
        float $cutLength,
        mixed $roll = null,
        ?RawMaterial $rawMaterial = null,
        ?ManufacturingProduct $product = null,
        float $targetQty = 0,
        float $purchaseRate = 0.0
    ): array {
        if ($cutLength <= 0) {
            return [];
        }

        // 1. Resolve RawMaterial if not passed directly
        if (!$rawMaterial && $roll) {
            $rawMaterial = $roll->rawMaterial ?? $roll->bale?->batch?->rawMaterial;
        }

        // 2. Resolve Purchase Rate if not passed
        if ($purchaseRate <= 0 && $roll && isset($roll->bale?->batch)) {
            $purchaseRate = (float) ($roll->bale->batch->purchase_rate ?: $roll->bale->batch->unit_cost ?: 0);
        }

        // 3. Resolve Roll Width & Units
        $widthVal = 0.0;
        $widthUnitStr = 'IN';

        if ($roll) {
            if (isset($roll->fabricWidth) && $roll->fabricWidth) {
                $fw = $roll->fabricWidth;
                $widthVal = (float) ($fw->value ?: $fw->width_inches ?: $fw->width ?: 0);
                $widthUnitStr = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'IN');
            } elseif (isset($roll->rawMaterial) && $roll->rawMaterial && $roll->rawMaterial->standard_width) {
                $widthVal = (float) $roll->rawMaterial->standard_width;
                $widthUnitStr = $roll->rawMaterial->width_unit ?: 'IN';
            }
        }

        if ($widthVal <= 0 && $rawMaterial && $rawMaterial->standard_width) {
            $widthVal = (float) $rawMaterial->standard_width;
            $widthUnitStr = $rawMaterial->width_unit ?: 'IN';
        }

        if ($widthVal <= 0) {
            $widthVal = 60.0;
            $widthUnitStr = 'IN';
        }

        // Convert width to Inches, CM, and Meters using single-source UnitConversionService
        $widthMeters = UnitConversionService::convert($widthVal, $widthUnitStr, 'M');
        $widthInches = UnitConversionService::convert($widthVal, $widthUnitStr, 'IN');
        $widthCm = UnitConversionService::convert($widthVal, $widthUnitStr, 'CM');

        // 4. Convert Cut Length to Meters using single-source UnitConversionService
        $cutLengthUnitStr = $rawMaterial?->unitModel?->short_code ?? ($rawMaterial?->unit ?: 'M');
        $cutLengthMeters = UnitConversionService::convert($cutLength, $cutLengthUnitStr, 'M');

        // 5. Compute Cut Area (m^2)
        $cutAreaM2 = $cutLengthMeters * $widthMeters;

        // 6. Resolve Product Piece Requirement Length (in Meters)
        $pieceReqLength = 2.5; // Default fallback
        if ($product && $rawMaterial) {
            $pieceReqLength = self::resolvePatternFabricLength($product, $rawMaterial);
        } elseif ($product && (float)$product->standard_fabric_length > 0) {
            $pieceReqLength = (float) $product->standard_fabric_length;
        }

        // 7. Calculate Yield & Target Consumption
        $estYieldPieces = $pieceReqLength > 0 ? (int) floor($cutLength / $pieceReqLength) : 0;
        $targetReqLength = $targetQty > 0 ? ($targetQty * $pieceReqLength) : 0.0;
        $targetReqAreaM2 = $targetReqLength * $widthMeters;

        $wastageLength = max(0.0, $cutLength - $targetReqLength);
        $wastageAreaM2 = $wastageLength * $widthMeters;
        $wastageCost = round($wastageLength * $purchaseRate, 2);

        $surplusPieces = max(0, $estYieldPieces - (int)$targetQty);
        $shortfallPieces = ($targetQty > 0 && $estYieldPieces < (int)$targetQty) ? ((int)$targetQty - $estYieldPieces) : 0;

        $normalizedUnit = UnitConversionService::normalizeAlias($widthUnitStr);
        if ($normalizedUnit === 'IN') {
            $widthDisplay = round($widthInches, 2) . '" (' . round($widthCm, 1) . ' cm / ' . round($widthMeters, 2) . ' m)';
        } elseif ($normalizedUnit === 'YD') {
            $widthDisplay = round($widthVal, 2) . ' YD (' . round($widthMeters, 2) . ' m)';
        } elseif ($normalizedUnit === 'CM') {
            $widthDisplay = round($widthCm, 1) . ' cm (' . round($widthMeters, 2) . ' m)';
        } elseif ($normalizedUnit === 'M') {
            $widthDisplay = round($widthMeters, 2) . ' m (' . round($widthCm, 1) . ' cm)';
        } elseif ($normalizedUnit === 'FT') {
            $widthDisplay = round($widthVal, 2) . ' FT (' . round($widthMeters, 2) . ' m / ' . round($widthCm, 1) . ' cm)';
        } else {
            $widthDisplay = round($widthVal, 2) . ' ' . $widthUnitStr . ' (' . round($widthCm, 1) . ' cm)';
        }

        $cutLengthUnitDisplay = (strtolower($cutLengthUnitStr) === 'm' || strtolower($cutLengthUnitStr) === 'meters') ? 'm' : $cutLengthUnitStr;
        $cutLengthDisplay = round($cutLength, 2) . ' ' . $cutLengthUnitDisplay;
        $dimensionsDisplay = "Width: {$widthDisplay} · Length: {$cutLengthDisplay}";

        return [
            'cut_length' => round($cutLength, 2),
            'cut_length_unit' => $cutLengthUnitStr,
            'cut_length_display' => $cutLengthDisplay,
            'roll_width_inches' => round($widthInches, 1),
            'roll_width_cm' => round($widthCm, 1),
            'roll_width_meters' => round($widthMeters, 4),
            'roll_width_display' => $widthDisplay,
            'dimensions_display' => $dimensionsDisplay,
            'cut_area_m2' => round($cutAreaM2, 2),
            'piece_req_length' => round($pieceReqLength, 2),
            'est_yield_pieces' => $estYieldPieces,
            'target_qty' => (int) $targetQty,
            'target_req_length' => round($targetReqLength, 2),
            'target_req_area_m2' => round($targetReqAreaM2, 2),
            'wastage_length' => round($wastageLength, 2),
            'wastage_area_m2' => round($wastageAreaM2, 2),
            'wastage_cost' => $wastageCost,
            'surplus_pieces' => $surplusPieces,
            'shortfall_pieces' => $shortfallPieces,
            'is_target_met' => $targetQty > 0 && $estYieldPieces >= $targetQty,
            'product_name' => $product?->name ?? 'Product Piece',
        ];
    }
}
