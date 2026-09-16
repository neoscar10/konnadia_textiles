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
     * Helper to format a single measurement value with unit into clean string representation.
     * If unit is meters ('M'), output e.g. "2 m".
     * If unit is non-meters (e.g. 'Inches'), output e.g. "44 Inches (1.12 m)" or "2.1 Inches (0.05 m)".
     */
    public static function formatSingleDimension(float $value, ?string $unitStr): string
    {
        if ($value <= 0) {
            return '—';
        }

        $unitStr = trim($unitStr ?? '');
        if (empty($unitStr)) {
            $unitStr = 'Meters';
        }

        $normUnit = UnitConversionService::normalizeAlias($unitStr);
        $formattedVal = (round($value, 4) == round($value, 0)) ? (string) round($value, 0) : (string) round($value, 2);

        if ($normUnit === 'M') {
            return "{$formattedVal} m";
        }

        $metersVal = self::convertToMeters($value, $unitStr);
        $formattedMeters = (round($metersVal, 4) == round($metersVal, 0)) ? (string) round($metersVal, 0) : (string) round($metersVal, 2);

        $unitLabel = match($normUnit) {
            'IN' => 'Inches',
            'CM' => 'cm',
            'YD' => 'yd',
            'FT' => 'ft',
            default => $unitStr,
        };

        return "{$formattedVal} {$unitLabel} ({$formattedMeters} m)";
    }

    /**
     * Resolve fabric width value, fabric_width_id, unit, and display text from any context.
     * $context can be InventoryBaleRoll, RawMaterial, FabricWidth, float/int width, or null.
     */
    public static function resolveWidthContext(mixed $context): array
    {
        $widthVal = 0.0;
        $fabricWidthId = null;
        $unitStr = 'IN';

        if ($context instanceof \App\Models\InventoryBaleRoll) {
            $context->loadMissing([
                'fabricWidth.unitModel',
                'rawMaterial.fabricWidths.unitModel',
                'bale.batch.rawMaterial.fabricWidths.unitModel'
            ]);

            if ($context->fabricWidth) {
                $fw = $context->fabricWidth;
                $widthVal = (float) ($fw->value ?: ($fw->width_inches ?: 0));
                $fabricWidthId = $fw->id;
                $unitStr = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'IN');
            } elseif ($context->fabric_width_id) {
                $fw = \App\Models\FabricWidth::with('unitModel')->find($context->fabric_width_id);
                if ($fw) {
                    $widthVal = (float) ($fw->value ?: ($fw->width_inches ?: 0));
                    $fabricWidthId = $fw->id;
                    $unitStr = $fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'IN');
                }
            }

            if ($widthVal <= 0) {
                $rawMat = $context->rawMaterial ?? $context->bale?->batch?->rawMaterial;
                if ($rawMat) {
                    $firstFw = $rawMat->fabricWidths?->first();
                    if ($firstFw) {
                        $widthVal = (float) ($firstFw->value ?: ($firstFw->width_inches ?: 0));
                        $fabricWidthId = $firstFw->id;
                        $unitStr = $firstFw->unitModel ? $firstFw->unitModel->short_code : ($firstFw->unit ?: 'IN');
                    } else {
                        $widthVal = (float) ($rawMat->standard_width ?: 0);
                        $fabricWidthId = property_exists($rawMat, 'fabric_width_id') ? $rawMat->fabric_width_id : null;
                        $unitStr = $rawMat->width_unit ?: 'IN';
                    }
                }
            }
        } elseif ($context instanceof RawMaterial) {
            $context->loadMissing(['fabricWidths.unitModel']);
            $firstFw = $context->fabricWidths?->first();
            if ($firstFw) {
                $widthVal = (float) ($firstFw->value ?: ($firstFw->width_inches ?: 0));
                $fabricWidthId = $firstFw->id;
                $unitStr = $firstFw->unitModel ? $firstFw->unitModel->short_code : ($firstFw->unit ?: 'IN');
            } else {
                $widthVal = (float) ($context->standard_width ?: 0);
                $fabricWidthId = property_exists($context, 'fabric_width_id') ? $context->fabric_width_id : null;
                $unitStr = $context->width_unit ?: 'IN';
            }
        } elseif ($context instanceof \App\Models\FabricWidth) {
            $context->loadMissing('unitModel');
            $widthVal = (float) ($context->value ?: ($context->width_inches ?: 0));
            $fabricWidthId = $context->id;
            $unitStr = $context->unitModel ? $context->unitModel->short_code : ($context->unit ?: 'IN');
        } elseif (is_numeric($context)) {
            $widthVal = (float) $context;
            $unitStr = 'IN';
        }

        $widthDisplay = self::formatSingleDimension($widthVal, $unitStr);

        return [
            'width_val' => $widthVal,
            'fabric_width_id' => $fabricWidthId,
            'unit' => $unitStr,
            'width_display' => $widthDisplay,
        ];
    }

    /**
     * Match pattern against fabric width context. Returns configuration status and resolved dimensions.
     */
    public static function resolvePatternFabricWidth(
        ManufacturingProductPattern $pattern,
        mixed $rawMaterialOrWidth = null
    ): array {
        $pattern->loadMissing('patternFabricWidths.fabricWidth.unitModel');
        $widthCtx = self::resolveWidthContext($rawMaterialOrWidth);

        $widthVal = $widthCtx['width_val'];
        $fabricWidthId = $widthCtx['fabric_width_id'];

        $matchedPfw = null;

        if ($pattern->patternFabricWidths->isNotEmpty()) {
            if ($fabricWidthId || $widthVal > 0) {
                $widthMeters = self::convertToMeters($widthVal, $widthCtx['unit']);

                foreach ($pattern->patternFabricWidths as $pfw) {
                    $fwModel = $pfw->fabricWidth;
                    if (!$fwModel && $pfw->fabric_width_id) {
                        $fwModel = \App\Models\FabricWidth::with('unitModel')->find($pfw->fabric_width_id);
                    }

                    $pfwWidthVal = (float) ($fwModel?->value ?: ($fwModel?->width_inches ?: ($fwModel?->width ?? 0)));
                    $pfwWidthUnit = $fwModel ? ($fwModel->unitModel ? $fwModel->unitModel->short_code : ($fwModel->unit ?: 'Inches')) : 'Inches';
                    $pfwWidthMeters = self::convertToMeters($pfwWidthVal, $pfwWidthUnit);
                    $pfwWidthId = $pfw->fabric_width_id;

                    if (
                        ($fabricWidthId && $pfwWidthId && (int)$pfwWidthId === (int)$fabricWidthId) ||
                        ($widthMeters > 0 && $pfwWidthMeters > 0 && abs($pfwWidthMeters - $widthMeters) < 0.02)
                    ) {
                        $matchedPfw = $pfw;
                        if ($fwModel && !$matchedPfw->relationLoaded('fabricWidth')) {
                            $matchedPfw->setRelation('fabricWidth', $fwModel);
                        }
                        break;
                    }
                }
            }

            // Fallback to first only if NO width context was specified at all (null)
            if (!$matchedPfw && $rawMaterialOrWidth === null) {
                $matchedPfw = $pattern->patternFabricWidths->first();
            }
        }

        if ($matchedPfw) {
            $length = (float) ($matchedPfw->fabric_length ?: 0);
            $lengthUnit = $matchedPfw->fabric_length_unit ?: ($pattern->fabric_length_unit ?: 'Meters');

            $fw = $matchedPfw->fabricWidth;
            $width = $fw ? (float) ($fw->value ?: ($fw->width_inches ?: 0)) : $widthVal;
            $widthUnit = $fw ? ($fw->unitModel ? $fw->unitModel->short_code : ($fw->unit ?: 'Inches')) : $widthCtx['unit'];

            return [
                'is_configured' => true,
                'pfw' => $matchedPfw,
                'length' => $length,
                'length_unit' => $lengthUnit,
                'width' => $width,
                'width_unit' => $widthUnit,
                'error_message' => null,
            ];
        }

        // If pattern has legacy fabric_length and no patternFabricWidths defined, and no specific width context was provided
        if ($pattern->patternFabricWidths->isEmpty() && (float) $pattern->fabric_length > 0 && $rawMaterialOrWidth === null) {
            return [
                'is_configured' => true,
                'pfw' => null,
                'length' => (float) $pattern->fabric_length,
                'length_unit' => $pattern->fabric_length_unit ?: 'Meters',
                'width' => $widthVal > 0 ? $widthVal : (float) ($pattern->fabricWidth?->value ?: 0),
                'width_unit' => $widthCtx['unit'],
                'error_message' => null,
            ];
        }

        // Unconfigured for this specific fabric width
        $widthLabel = $widthCtx['width_display'] !== '—' ? $widthCtx['width_display'] : "{$widthVal} Inches";
        $patternName = $pattern->name ?: 'Standard';

        return [
            'is_configured' => false,
            'pfw' => null,
            'length' => 0.0,
            'length_unit' => 'Meters',
            'width' => $widthVal,
            'width_unit' => $widthCtx['unit'],
            'error_message' => "No fabric length defined for {$widthLabel} on pattern '{$patternName}'. Please configure length for this fabric width in product pattern settings.",
        ];
    }

    /**
     * Resolve standard fabric length consumption per piece for a specific product/pattern at a given roll fabric width.
     */
    public static function resolvePatternFabricLength(ManufacturingProduct $product, mixed $rawMaterialOrWidth = null, ?int $patternId = null): float
    {
        $pattern = null;
        if ($patternId) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth.unitModel')->find($patternId);
        }
        if (!$pattern) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth.unitModel')
                ->where('manufacturing_product_id', $product->id)
                ->where('is_default', true)
                ->first();
        }
        if (!$pattern) {
            $pattern = ManufacturingProductPattern::with('patternFabricWidths.fabricWidth.unitModel')
                ->where('manufacturing_product_id', $product->id)
                ->first();
        }

        if ($pattern) {
            $resolved = self::resolvePatternFabricWidth($pattern, $rawMaterialOrWidth);
            if ($resolved['is_configured'] && $resolved['length'] > 0) {
                return (float) $resolved['length'];
            }
            if (!$resolved['is_configured']) {
                return 0.0;
            }
        }

        return (float) ($product->standard_fabric_length ?: 0.0);
    }

    /**
     * Calculate product/pattern surface area in square meters (m^2).
     */
    public static function calculateProductPatternAreaM2(
        ?ManufacturingProduct $product,
        ?ManufacturingProductPattern $pattern = null,
        mixed $rawMaterialOrWidth = null
    ): float {
        if (!$product && !$pattern) {
            return 0.0;
        }

        if ($pattern && !$product) {
            $product = $pattern->manufacturingProduct;
        }

        if (!$pattern && $product) {
            $pattern = $product->defaultPattern ?? $product->patterns()->first();
        }

        if ($pattern) {
            $resolved = self::resolvePatternFabricWidth($pattern, $rawMaterialOrWidth);
            if (!$resolved['is_configured'] || $resolved['length'] <= 0 || $resolved['width'] <= 0) {
                if ($rawMaterialOrWidth !== null) {
                    $resolved = self::resolvePatternFabricWidth($pattern, null);
                }
            }

            if (!$resolved['is_configured'] || $resolved['length'] <= 0 || $resolved['width'] <= 0) {
                return 0.0;
            }

            $lengthMeters = self::convertToMeters($resolved['length'], $resolved['length_unit']);
            $widthMeters = self::convertToMeters($resolved['width'], $resolved['width_unit']);

            return round($lengthMeters * $widthMeters, 4);
        }

        if ($product) {
            $length = (float) ($product->standard_fabric_length ?: 0);
            $width = (float) ($product->standard_fabric_width ?: 0);

            if ($length <= 0 || $width <= 0) {
                return 0.0;
            }

            $lengthMeters = self::convertToMeters($length, $product->fabric_length_unit ?: 'Meters');
            $widthMeters = self::convertToMeters($width, $product->fabric_width_unit ?: 'Centimeters');

            return round($lengthMeters * $widthMeters, 4);
        }

        return 0.0;
    }

    /**
     * Format dimensions (Length and Width) of a Product/Pattern into clean string representations.
     */
    public static function formatProductPatternDimensions(
        ?ManufacturingProduct $product,
        ?ManufacturingProductPattern $pattern = null,
        mixed $rawMaterialOrWidth = null
    ): array {
        if (!$product && !$pattern) {
            return [
                'is_configured' => false,
                'length_val' => 0.0,
                'length_unit' => 'M',
                'length_display' => '—',
                'width_val' => 0.0,
                'width_unit' => 'IN',
                'width_display' => '—',
                'dimensions_display' => 'Length: — · Width: —',
                'piece_area_m2' => 0.0,
                'area_display' => '0 m²',
                'error_message' => null,
            ];
        }

        if ($pattern && !$product) {
            $product = $pattern->manufacturingProduct;
        }

        if (!$pattern && $product) {
            $pattern = $product->defaultPattern ?? $product->patterns()->first();
        }

        if ($pattern) {
            $res = self::resolvePatternFabricWidth($pattern, $rawMaterialOrWidth);

            if (!$res['is_configured']) {
                $widthDisplay = self::formatSingleDimension($res['width'], $res['width_unit']);
                return [
                    'is_configured' => false,
                    'length_val' => 0.0,
                    'length_unit' => 'Meters',
                    'length_display' => 'Not Defined',
                    'width_val' => (float) $res['width'],
                    'width_unit' => $res['width_unit'],
                    'width_display' => $widthDisplay,
                    'dimensions_display' => "Length: Not Defined · Width: {$widthDisplay}",
                    'piece_area_m2' => 0.0,
                    'area_display' => 'Undefined (Config Required)',
                    'error_message' => $res['error_message'],
                ];
            }

            $length = $res['length'];
            $lengthUnit = $res['length_unit'];
            $width = $res['width'];
            $widthUnit = $res['width_unit'];

            $lengthDisplay = self::formatSingleDimension($length, $lengthUnit);
            $widthDisplay = self::formatSingleDimension($width, $widthUnit);

            $lengthMeters = self::convertToMeters($length, $lengthUnit);
            $widthMeters = self::convertToMeters($width, $widthUnit);

            $pieceAreaM2 = round($lengthMeters * $widthMeters, 4);

            return [
                'is_configured' => true,
                'length_val' => (float) $length,
                'length_unit' => $lengthUnit,
                'length_display' => $lengthDisplay,
                'width_val' => (float) $width,
                'width_unit' => $widthUnit,
                'width_display' => $widthDisplay,
                'dimensions_display' => "Length: {$lengthDisplay} · Width: {$widthDisplay}",
                'piece_area_m2' => $pieceAreaM2,
                'area_display' => "{$pieceAreaM2} m²",
                'error_message' => null,
            ];
        }

        $length = (float) ($product->standard_fabric_length ?: 0);
        $lengthUnit = $product->fabric_length_unit ?: 'Meters';
        $width = (float) ($product->standard_fabric_width ?: 0);
        $widthUnit = $product->fabric_width_unit ?: 'Inches';

        $lengthDisplay = self::formatSingleDimension($length, $lengthUnit);
        $widthDisplay = self::formatSingleDimension($width, $widthUnit);

        $lengthMeters = self::convertToMeters($length, $lengthUnit);
        $widthMeters = self::convertToMeters($width, $widthUnit);
        $pieceAreaM2 = round($lengthMeters * $widthMeters, 4);

        return [
            'is_configured' => true,
            'length_val' => (float) $length,
            'length_unit' => $lengthUnit,
            'length_display' => $lengthDisplay,
            'width_val' => (float) $width,
            'width_unit' => $widthUnit,
            'width_display' => $widthDisplay,
            'dimensions_display' => "Length: {$lengthDisplay} · Width: {$widthDisplay}",
            'piece_area_m2' => $pieceAreaM2,
            'area_display' => "{$pieceAreaM2} m²",
            'error_message' => null,
        ];
    }

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
    public static function calculateCutArea(float $cutLength, mixed $rawMaterialOrRoll): float
    {
        if ($cutLength <= 0) {
            return 0.0;
        }

        $cutLengthMeters = self::convertToMeters($cutLength, 'Meters');

        $widthCtx = self::resolveWidthContext($rawMaterialOrRoll);
        $widthVal = $widthCtx['width_val'];
        $unitStr = $widthCtx['unit'];

        if ($widthVal <= 0) {
            $widthVal = 60.0;
            $unitStr = 'IN';
        }

        $widthMeters = self::convertToMeters($widthVal, $unitStr);

        return round($cutLengthMeters * $widthMeters, 4);
    }

    /**
     * Compute full cutting area breakdown & area-weighted wastage cost allocation:
     * - Cut Area & Required Standard Length
     * - Total Used Area (Base Unit^2)
     * - Cutting Wastage Length & Cost
     * - Area-Weighted Cost Allocation per Product (so larger area products bear higher waste cost)
     */
    public static function computeCuttingBreakdown(float $cutLength, mixed $rawMaterial, array $productOutputs, float $purchaseRate = 0.0): array
    {
        $rawMaterialOrRoll = $rawMaterial;
        $rawMaterial = $rawMaterialOrRoll instanceof RawMaterial 
            ? $rawMaterialOrRoll 
            : ($rawMaterialOrRoll?->rawMaterial ?? $rawMaterialOrRoll?->bale?->rawMaterial ?? $rawMaterialOrRoll?->bale?->batch?->rawMaterial);

        $unitGroupId = $rawMaterial?->unit_group_id;
        $cutAreaBase = self::calculateCutArea($cutLength, $rawMaterialOrRoll);
        
        $widthCtx = self::resolveWidthContext($rawMaterialOrRoll);
        $widthVal = $widthCtx['width_val'] > 0 ? $widthCtx['width_val'] : (float) ($rawMaterial?->standard_width ?: 60);
        $widthUnitStr = $widthCtx['unit'] ?: ($rawMaterial?->width_unit ?: 'IN');
        $widthMeters = self::convertToMeters($widthVal, $widthUnitStr);

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

            $pattern = $patternId ? ManufacturingProductPattern::find($patternId) : null;

            $pieceAreaBase = self::calculateProductPatternAreaM2($product, $pattern, $rawMaterialOrRoll);
            $itemTotalUsedAreaBase = $pieceAreaBase * $qty;
            $totalUsedAreaBase += $itemTotalUsedAreaBase;

            $pieceReqLength = self::resolvePatternFabricLength($product, $rawMaterialOrRoll, $patternId);
            $itemReqLength = $pieceReqLength * $qty;
            $totalStandardRequiredLength += $itemReqLength;

            $itemDetails = [
                'product_id' => $product->id,
                'pattern_id' => $patternId,
                'name' => $product->name,
                'code' => $product->code,
                'piece_area_base' => $pieceAreaBase,
                'quantity' => $qty,
                'piece_req_length' => $pieceReqLength,
                'total_req_length' => $itemReqLength,
                'total_used_area_base' => $itemTotalUsedAreaBase,
            ];

            $productDetails[$productId] = $itemDetails;
            if ($patternId) {
                $productDetails["{$productId}_{$patternId}"] = $itemDetails;
            }
        }

        $remainingAreaBase = max(0.0, $cutAreaBase - $totalUsedAreaBase);
        $isOverCapacity = $totalUsedAreaBase > ($cutAreaBase + 0.0001);

        // Wastage length based on pattern standard requirement (or area remaining)
        $patternWastageLength = max(0.0, $cutLength - $totalStandardRequiredLength);

        // Auto wastage length in Base Unit (Meters) = Remaining Area (m^2) / Width (m)
        $wastageLengthBase = $widthMeters > 0 ? ($remainingAreaBase / $widthMeters) : 0.0;
        $wastageLengthDisplay = $wastageLengthBase;

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
        $pieceReqLength = 0.0;
        if ($product && $roll) {
            $pieceReqLength = self::resolvePatternFabricLength($product, $roll);
        } elseif ($product && $rawMaterial) {
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

        $widthDisplay = self::formatSingleDimension($widthVal, $widthUnitStr);
        $cutLengthDisplay = self::formatSingleDimension($cutLength, $cutLengthUnitStr);
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
