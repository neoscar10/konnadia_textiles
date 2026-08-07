<?php

namespace App\Services;

use App\Models\ProductionJob;
use App\Models\InventoryBatch;
use App\Models\JobMaterialConsumption;
use App\Models\JobProductionOutput;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class FabricCostingService
{
    /**
     * Normalize length and width dimensions to inches.
     */
    public function convertToInches(float $value, string $unit): float
    {
        $unitLower = strtolower(trim($unit));
        if ($unitLower === 'inch' || $unitLower === 'inches' || $unitLower === 'in') {
            return $value;
        }
        if ($unitLower === 'cm' || $unitLower === 'cms' || $unitLower === 'centimeter' || $unitLower === 'centimeters') {
            return $value * 0.393701;
        }
        if ($unitLower === 'meter' || $unitLower === 'meters' || $unitLower === 'm') {
            return $value * 39.3701;
        }
        if ($unitLower === 'yard' || $unitLower === 'yards' || $unitLower === 'yd') {
            return $value * 36.0;
        }
        if ($unitLower === 'ft' || $unitLower === 'feet' || $unitLower === 'foot') {
            return $value * 12.0;
        }

        return \App\Services\UnitConversionService::convert($value, $unit, 'Inches');
    }

    /**
     * Compute surface area in square inches.
     */
    public function calculateArea(float $width, string $widthUnit, float $length, string $lengthUnit): float
    {
        $wIn = $this->convertToInches($width, $widthUnit);
        $lIn = $this->convertToInches($length, $lengthUnit);
        return $wIn * $lIn;
    }

    /**
     * Calculate and allocate fabric costs to cut pieces.
     *
     * @param int $jobId
     * @param int $fabricBatchId
     * @param float $consumedLength (Quantity consumed from fabric batch)
     * @param array $cutPiecesArray [ ['manufacturing_product_id' => X, 'width' => W, 'length' => L, 'width_unit' => 'inch', 'length_unit' => 'meter', 'quantity' => Q], ... ]
     * @param float $wastageLength
     * @param float $fabricWidth (Default 60.00 inches)
     * @return array
     */
    public function calculateFabricCostAllocation(
        int $jobId,
        int $fabricBatchId,
        float $consumedLength,
        array $cutPiecesArray,
        float $wastageLength,
        float $fabricWidth = 60.00
    ): array {
        return DB::transaction(function () use ($jobId, $fabricBatchId, $consumedLength, $cutPiecesArray, $wastageLength, $fabricWidth) {
            $job = ProductionJob::findOrFail($jobId);
            $batch = InventoryBatch::with('rawMaterial.category')->findOrFail($fabricBatchId);

            $purchaseRate = (float) $batch->unit_cost;
            $lengthUnit = $batch->unit ?: 'Meters';

            // 1. Total Fabric Cost Consumed
            $totalFabricCost = $consumedLength * $purchaseRate;

            // 2. Total Fabric Area Consumed (in square inches)
            $consumedLengthInches = $this->convertToInches($consumedLength, $lengthUnit);
            $totalFabricArea = $fabricWidth * $consumedLengthInches;

            // 3. Total Wastage Cost
            $totalWastageCost = $wastageLength * $purchaseRate;

            // 4. Calculate individual areas & total output area
            $items = [];
            $totalOutputArea = 0.0;

            foreach ($cutPiecesArray as $index => $piece) {
                $width = (float) $piece['width'];
                $length = (float) $piece['length'];
                $widthUnit = $piece['width_unit'] ?: 'inch';
                $lengthUnitPiece = $piece['length_unit'] ?: 'meter';
                $qty = (int) $piece['quantity'];

                $singleArea = $this->calculateArea($width, $widthUnit, $length, $lengthUnitPiece);
                $totalArea = $singleArea * $qty;
                $totalOutputArea += $totalArea;

                $items[] = [
                    'manufacturing_product_id' => $piece['manufacturing_product_id'],
                    'width' => $width,
                    'length' => $length,
                    'width_unit' => $widthUnit,
                    'length_unit' => $lengthUnitPiece,
                    'quantity' => $qty,
                    'single_area' => $singleArea,
                    'total_area' => $totalArea,
                ];
            }

            // 5. Distribute Costs
            $breakdown = [];
            foreach ($items as &$item) {
                // A. Base Fabric Cost: (Area of Cut Piece / Total Fabric Area Consumed) * Total Batch Purchase Cost Consumed
                if ($totalFabricArea > 0) {
                    $baseCost = ($item['total_area'] / $totalFabricArea) * $totalFabricCost;
                } else {
                    $baseCost = 0.0;
                }

                // B. Fabric Wastage Cost: (Cut Piece Area / Total Area of All Cut Pieces Produced) * Total Fabric Wastage Cost
                if ($totalOutputArea > 0) {
                    $allocatedWastage = ($item['total_area'] / $totalOutputArea) * $totalWastageCost;
                } else {
                    $allocatedWastage = 0.0;
                }

                $itemTotalCost = $baseCost + $allocatedWastage;
                $costPerUnit = $item['quantity'] > 0 ? ($itemTotalCost / $item['quantity']) : 0.0;

                $breakdown[] = [
                    'manufacturing_product_id' => $item['manufacturing_product_id'],
                    'quantity' => $item['quantity'],
                    'single_area' => $item['single_area'],
                    'total_area' => $item['total_area'],
                    'base_cost' => $baseCost,
                    'allocated_wastage' => $allocatedWastage,
                    'total_cost' => $itemTotalCost,
                    'cost_per_unit' => $costPerUnit,
                ];

                // 6. Save or update JobProductionOutput costing details
                JobProductionOutput::updateOrCreate(
                    [
                        'production_job_id' => $jobId,
                        'manufacturing_product_id' => $item['manufacturing_product_id'],
                        'task_id' => Task::where('name', 'Cutting')->first()->id ?? 1,
                    ],
                    [
                        'job_code' => $job->job_code,
                        'quantity_produced' => $item['quantity'],
                        'inventory_batch_id' => $fabricBatchId,
                        'fabric_width' => $item['width'],
                        'fabric_length' => $item['length'],
                        'fabric_width_unit' => $item['width_unit'],
                        'fabric_length_unit' => $item['length_unit'],
                        'calculated_base_cost' => $baseCost,
                        'allocated_wastage_cost' => $allocatedWastage,
                        'total_fabric_cost' => $itemTotalCost,
                    ]
                );
            }

            // 7. Record/Update the JobMaterialConsumption details
            $totalBaseCostSum = array_sum(array_column($breakdown, 'base_cost'));
            $totalWastageCostSum = array_sum(array_column($breakdown, 'allocated_wastage'));

            JobMaterialConsumption::updateOrCreate(
                [
                    'production_job_id' => $jobId,
                    'inventory_batch_id' => $fabricBatchId,
                    'task_id' => Task::where('name', 'Cutting')->first()->id ?? 1,
                ],
                [
                    'job_code' => $job->job_code,
                    'quantity_consumed' => $consumedLength,
                    'unit_cost' => $purchaseRate,
                    'total_cost' => $totalFabricCost,
                    'consumed_length' => $consumedLength,
                    'calculated_base_cost' => $totalBaseCostSum,
                    'allocated_wastage_cost' => $totalWastageCostSum,
                    'total_fabric_cost' => $totalFabricCost,
                ]
            );

            // Decrement the inventory batch balance
            // Note: Defer decrement logic to the controller/Livewire component or manage here.
            // Since we use updateOrCreate, if this is called multiple times we must be careful not to double-decrement.
            // Let's assume the controller or Livewire component handles decrements when recording.

            return [
                'total_fabric_cost_consumed' => $totalFabricCost,
                'total_wastage_cost' => $totalWastageCost,
                'total_output_area' => $totalOutputArea,
                'itemized_breakdown' => $breakdown,
            ];
        });
    }
}
