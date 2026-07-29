<?php

namespace App\Services\Manufacturing;

use App\Models\ProductionBatch;
use App\Models\ProductionJob;
use App\Models\JobMaterialConsumption;
use App\Models\JobLaborAllocation;
use App\Models\JobWastage;
use App\Models\JobProductionOutput;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\JsonResponse;

class ProductionCostingService
{
    use ApiResponseTrait;

    /**
     * Calculate Raw Material Costs categorized by Fabric, Subsidiary, and Packaging materials.
     * Cost = Actual Quantity Consumed x Batch Purchase Rate (unit_cost).
     *
     * @param mixed $batchId
     * @return array
     */
    public function calculateMaterialCosts($batchId): array
    {
        $consumptions = JobMaterialConsumption::whereHas('job', function ($q) use ($batchId) {
            $q->where('production_batch_db_id', $batchId);
        })
        ->with(['inventoryBatch.rawMaterial.category'])
        ->get();

        $fabricCost = 0.0;
        $subsidiaryCost = 0.0;
        $packagingCost = 0.0;
        $otherMaterialCost = 0.0;
        $fabricConsumedQty = 0.0;

        foreach ($consumptions as $item) {
            $cost = (float) $item->total_cost;
            if ($cost <= 0 && $item->inventoryBatch) {
                $cost = (float) $item->quantity_consumed * (float) $item->inventoryBatch->unit_cost;
            }

            $categoryName = strtolower($item->inventoryBatch?->rawMaterial?->category?->name ?? '');

            if (str_contains($categoryName, 'fabric') || str_contains($categoryName, 'textile')) {
                $fabricCost += $cost;
                $fabricConsumedQty += (float) $item->quantity_consumed;
            } elseif (str_contains($categoryName, 'subsidiary') || str_contains($categoryName, 'trimming') || str_contains($categoryName, 'thread') || str_contains($categoryName, 'button')) {
                $subsidiaryCost += $cost;
            } elseif (str_contains($categoryName, 'pack') || str_contains($categoryName, 'box') || str_contains($categoryName, 'bag')) {
                $packagingCost += $cost;
            } else {
                $otherMaterialCost += $cost;
            }
        }

        $totalMaterialCost = $fabricCost + $subsidiaryCost + $packagingCost + $otherMaterialCost;

        return [
            'fabric_cost' => round($fabricCost, 2),
            'fabric_consumed_qty' => round($fabricConsumedQty, 2),
            'subsidiary_cost' => round($subsidiaryCost, 2),
            'packaging_cost' => round($packagingCost, 2),
            'other_material_cost' => round($otherMaterialCost, 2),
            'total_material_cost' => round($totalMaterialCost, 2),
            'items' => $consumptions,
        ];
    }

    /**
     * Calculate Labor Costs (Sum of all calculated_wage values from Piece-rate/Job Work labor allocations).
     *
     * @param mixed $batchId
     * @return array
     */
    public function calculateLaborCosts($batchId): array
    {
        $allocations = JobLaborAllocation::whereHas('job', function ($q) use ($batchId) {
            $q->where('production_batch_db_id', $batchId);
        })
        ->with(['labor', 'task'])
        ->get();

        $totalLaborCost = (float) $allocations->sum('calculated_wage');

        return [
            'total_labor_cost' => round($totalLaborCost, 2),
            'allocations' => $allocations,
        ];
    }

    /**
     * Calculate Production Loss & Wastage Costs.
     * Unused fabric from cutting is treated as Fabric Wastage and its cost is distributed
     * among the cut pieces using a Weighted Average Distribution.
     *
     * @param mixed $batchId
     * @return array
     */
    public function calculateWastageCosts($batchId): array
    {
        $materialData = $this->calculateMaterialCosts($batchId);
        $avgFabricRate = $materialData['fabric_consumed_qty'] > 0
            ? ($materialData['fabric_cost'] / $materialData['fabric_consumed_qty'])
            : 0.0;

        $wastages = JobWastage::whereHas('job', function ($q) use ($batchId) {
            $q->where('production_batch_db_id', $batchId);
        })
        ->with(['manufacturingProduct', 'task'])
        ->get();

        $totalWastageCost = 0.0;
        $wastageLog = [];

        foreach ($wastages as $w) {
            $qty = (float) $w->quantity_wasted;
            // Cost calculation: If fabric scrap, use average fabric purchase rate
            $cost = $qty * ($avgFabricRate > 0 ? $avgFabricRate : 50.0);
            $totalWastageCost += $cost;

            $wastageLog[] = [
                'id' => $w->id,
                'task_name' => $w->task?->name ?? 'General',
                'product_name' => $w->manufacturingProduct?->name ?? 'Fabric Scraps',
                'quantity_wasted' => $qty,
                'unit_cost' => round($avgFabricRate > 0 ? $avgFabricRate : 50.0, 2),
                'total_cost' => round($cost, 2),
                'reason' => $w->reason,
            ];
        }

        return [
            'total_wastage_cost' => round($totalWastageCost, 2),
            'avg_fabric_rate' => round($avgFabricRate, 2),
            'wastage_log' => $wastageLog,
        ];
    }

    /**
     * Get Complete Batch Cost Summary DTO / Array containing:
     * - total_material_cost (fabric, subsidiary, packaging)
     * - total_labor_cost
     * - total_wastage_cost
     * - total_manufacturing_cost
     * - average_cost_per_unit
     *
     * @param mixed $batchId
     * @return array
     */
    public function getBatchCostSummary($batchId): array
    {
        $batch = ProductionBatch::findOrFail($batchId);

        $material = $this->calculateMaterialCosts($batchId);
        $labor = $this->calculateLaborCosts($batchId);
        $wastage = $this->calculateWastageCosts($batchId);

        $totalMaterialCost = $material['total_material_cost'];
        $totalLaborCost = $labor['total_labor_cost'];
        $totalWastageCost = $wastage['total_wastage_cost'];

        // Total Manufacturing Cost = Material Cost + Labor Cost + Wastage Cost
        $totalManufacturingCost = round($totalMaterialCost + $totalLaborCost + $totalWastageCost, 2);

        // Total Finished Quantity produced
        $totalFinishedUnits = (int) JobProductionOutput::whereHas('job', function ($q) use ($batchId) {
            $q->where('production_batch_db_id', $batchId);
        })->sum('quantity_produced');

        if ($totalFinishedUnits <= 0) {
            // Fallback to latest stage processed quantity if output yields not explicitly logged
            $lastJob = ProductionJob::where('production_batch_db_id', $batchId)->latest('id')->first();
            if ($lastJob) {
                $totalFinishedUnits = (int) JobLaborAllocation::where('job_id', $lastJob->job_code)->sum('quantity_processed');
            }
        }
        if ($totalFinishedUnits <= 0) {
            $totalFinishedUnits = (int) $batch->planned_quantity;
        }

        $averageCostPerUnit = round($totalManufacturingCost / max(1, $totalFinishedUnits), 2);

        return [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'planned_quantity' => $batch->planned_quantity,
            'finished_units' => $totalFinishedUnits,
            'fabric_cost' => $material['fabric_cost'],
            'subsidiary_cost' => $material['subsidiary_cost'],
            'packaging_cost' => $material['packaging_cost'],
            'other_material_cost' => $material['other_material_cost'],
            'total_material_cost' => $totalMaterialCost,
            'total_labor_cost' => $totalLaborCost,
            'total_wastage_cost' => $totalWastageCost,
            'total_manufacturing_cost' => $totalManufacturingCost,
            'average_cost_per_unit' => $averageCostPerUnit,
            'material_details' => $material,
            'labor_details' => $labor,
            'wastage_details' => $wastage,
        ];
    }

    /**
     * Database Caching: Lock/update cost summary columns in production_batches table.
     *
     * @param mixed $batchId
     * @return ProductionBatch
     */
    public function cacheBatchCostSummary($batchId): ProductionBatch
    {
        $summary = $this->getBatchCostSummary($batchId);
        $batch = ProductionBatch::findOrFail($batchId);

        $batch->update([
            'total_material_cost' => $summary['total_material_cost'],
            'total_labor_cost' => $summary['total_labor_cost'],
            'total_wastage_cost' => $summary['total_wastage_cost'],
            'total_manufacturing_cost' => $summary['total_manufacturing_cost'],
            'average_unit_cost' => $summary['average_cost_per_unit'],
        ]);

        return $batch;
    }
}
