<?php

namespace App\Services\Manufacturing;

use App\Models\InventoryBatch;
use App\Models\JobMaterialConsumption;
use App\Models\StitchingCostPool;
use App\Models\ProductionBatch;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ProductionCostingService
 *
 * Handles granular batch-level costing (fabric, subsidiary, packaging, labor, wastage), periodic stitching cost pool
 * accumulation, overhead/consumables costing, and per-finished-unit average cost calculation.
 */
class ProductionCostingService
{
    /**
     * Accumulate all stitching material consumption costs within a date range
     * into a StitchingCostPool record.
     */
    public function accumulateStitchingCostPool($startDate, $endDate, string $periodName = ''): StitchingCostPool
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        if (empty($periodName)) {
            $periodName = $start->format('F Y');
        }

        // Sum all job-level stitching consumption costs in the period
        $jobConsumptionTotal = JobMaterialConsumption::whereBetween('created_at', [$start, $end])
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-STITCH'))
            ->sum('total_cost');

        // Additionally sum any purchase lot costs received into stock for CAT-STITCH
        $purchaseLotTotal = InventoryBatch::whereBetween('created_at', [$start, $end])
            ->whereHas('rawMaterial.category', fn($q) => $q->where('code', 'CAT-STITCH'))
            ->selectRaw('SUM(quantity_received * purchase_rate) as total')
            ->value('total') ?? 0;

        $totalStitchingCost = round((float) $jobConsumptionTotal + (float) $purchaseLotTotal, 2);

        $pool = StitchingCostPool::create([
            'period_name'         => $periodName,
            'start_date'          => $start->toDateString(),
            'end_date'            => $end->toDateString(),
            'total_stitching_cost' => $totalStitchingCost,
            'status'              => 'open',
        ]);

        return $pool;
    }

    /**
     * Close a stitching cost pool and mark it as allocated.
     */
    public function closeStitchingCostPool(StitchingCostPool $pool): StitchingCostPool
    {
        $pool->update(['status' => 'allocated']);
        return $pool;
    }

    /**
     * Get the total open stitching cost for all open pools.
     */
    public function totalOpenStitchingCost(): float
    {
        return (float) StitchingCostPool::where('status', 'open')->sum('total_stitching_cost');
    }

    /**
     * Fetch a 360-degree cost rollup breakdown for a given production batch.
     */
    public function getBatchCostSummary(int $batchId): array
    {
        $batch = ProductionBatch::findOrFail($batchId);

        // Auto-link any jobs matching this batch_code that have empty production_batch_db_id
        if (!empty($batch->batch_code)) {
            \App\Models\ProductionJob::where('production_batch_id', $batch->batch_code)
                ->whereNull('production_batch_db_id')
                ->update(['production_batch_db_id' => $batch->id]);
        }

        // Get all job IDs and job codes for this batch
        $batchJobs = \App\Models\ProductionJob::where('production_batch_db_id', $batch->id)
            ->orWhere('production_batch_id', $batch->batch_code)
            ->get();

        $jobIds   = $batchJobs->pluck('id');
        $jobCodes = $batchJobs->pluck('job_code');

        // 1. Fabric cost
        $fabricCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-FAB')
                       ->orWhere('code', 'like', '%FAB%')
                       ->orWhere('name', 'like', '%Fabric%')
                       ->orWhere('unit_type', 'length_based');
                })
                ->orWhereNotNull('inventory_bale_roll_id')
                ->orWhere('consumed_length', '>', 0)
                ->orWhere('total_fabric_cost', '>', 0);
            })
            ->sum('total_cost');

        if ($fabricCost === 0.0) {
            $fabricCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                ->sum('total_fabric_cost');
        }

        if ($fabricCost === 0.0) {
            $fabricCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                ->whereNotNull('inventory_bale_roll_id')
                ->sum('total_cost');
        }

        // 2. Subsidiary cost
        $subsidiaryCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-SUB')
                       ->orWhere('code', 'like', '%SUB%')
                       ->orWhere('name', 'like', '%Subsidiary%')
                       ->orWhere('name', 'like', '%Trim%');
                })
                ->orWhereHas('inventoryBatch.rawMaterial', function ($rmq) {
                    $rmq->where('name', 'like', '%button%')
                        ->orWhere('name', 'like', '%zipper%')
                        ->orWhere('name', 'like', '%thread%')
                        ->orWhere('name', 'like', '%elastic%')
                        ->orWhere('name', 'like', '%label%')
                        ->orWhere('name', 'like', '%sub%');
                })
                ->orWhere(function ($subQ) {
                    $subQ->whereNull('inventory_bale_roll_id')
                         ->where(function($lq) { $lq->whereNull('consumed_length')->orWhere('consumed_length', 0); })
                         ->where(function($fq) { $fq->whereNull('total_fabric_cost')->orWhere('total_fabric_cost', 0); })
                         ->whereDoesntHave('inventoryBatch.rawMaterial.category', function ($cq) {
                             $cq->where('code', 'CAT-FAB')
                                ->orWhere('code', 'like', '%FAB%')
                                ->orWhere('name', 'like', '%Fabric%')
                                ->orWhere('unit_type', 'length_based');
                         });
                });
            })
            ->sum('total_cost');

        // 3. Packaging cost
        $packagingCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-PKG')->orWhere('code', 'like', '%PKG%'))
            ->sum('total_cost');

        // 4. General Overheads cost (CAT-OHD)
        $overheadDirectCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-OHD')->orWhere('code', 'like', '%OHD%'))
            ->sum('total_cost');
        $overheadAllocatedCost = (float) DB::table('overhead_cost_allocations')
            ->where('production_batch_id', $batch->id)
            ->sum('allocated_cost');
        $overheadCost = $overheadDirectCost + $overheadAllocatedCost;

        // 5. Stitching cost (allocates pro-rata from pool if no direct consumptions)
        $stitchingCost = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-STITCH')->orWhere('code', 'like', '%STITCH%'))
            ->sum('total_cost');
        if ($stitchingCost === 0.0) {
            $batchDate = $batch->batch_date ?: now();
            $pool = StitchingCostPool::where('start_date', '<=', $batchDate)
                ->where('end_date', '>=', $batchDate)
                ->first();
            if ($pool) {
                $totalFinishedInPeriod = ProductionBatch::whereBetween('batch_date', [$pool->start_date, $pool->end_date])
                    ->get()
                    ->sum(fn($b) => (int) ($b->total_finished_quantity ?: $b->planned_quantity));
                if ($totalFinishedInPeriod > 0) {
                    $batchQty = (int) ($batch->total_finished_quantity ?: $batch->planned_quantity);
                    $stitchingCost = round(($batchQty / $totalFinishedInPeriod) * (float) $pool->total_stitching_cost, 2);
                }
            }
        }

        // Total Material Cost
        $totalMaterialCost = $fabricCost + $subsidiaryCost + $stitchingCost + $packagingCost + $overheadCost;

        // Labor Wages
        $totalLaborCost = (float) \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)->sum('calculated_wage');

        // Wastage Log and Costs (Single-source audit without double counting)
        $defaultFabricRate = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-FAB'))
            ->avg('unit_cost') ?: 150.00;

        $wastageLog = [];
        $wastages = \App\Models\JobWastage::whereIn('production_job_id', $jobIds)->with(['manufacturingProduct', 'task', 'inventoryBaleRoll.bale'])->get();

        foreach ($wastages as $w) {
            // Determine unit cost for this wastage entry
            $unitCost = $defaultFabricRate;
            if ($w->inventoryBaleRoll?->bale?->unit_cost) {
                $unitCost = (float) $w->inventoryBaleRoll->bale->unit_cost;
            }

            $qty = (float) $w->quantity_wasted;
            $wCost = round($qty * $unitCost, 2);

            // Construct descriptive title
            $reason = trim($w->reason ?? '');
            $prodName = $w->manufacturingProduct?->name;

            if (!empty($reason)) {
                $title = $reason;
                if ($prodName && !str_contains(strtolower($reason), strtolower($prodName))) {
                    $title .= " ({$prodName})";
                }
            } else {
                $title = $prodName ? "Defective Piece - {$prodName}" : "Damaged Material / Scrap";
            }

            $wastageLog[] = [
                'product_name'    => $title,
                'task_name'       => $w->task?->name ?? 'Production',
                'quantity_wasted' => $qty,
                'unit_cost'       => $unitCost,
                'total_cost'      => $wCost,
            ];
        }

        $totalWastageCost = array_sum(array_column($wastageLog, 'total_cost'));
        if ($totalWastageCost === 0.0) {
            $allocatedWastage = (float) JobMaterialConsumption::whereIn('production_job_id', $jobIds)->sum('allocated_wastage_cost');
            if ($allocatedWastage > 0) {
                $totalWastageCost = $allocatedWastage;
            } else {
                $cuttingConsumptions = JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                    ->where(function($q) {
                        $q->whereNotNull('inventory_bale_roll_id')->orWhere('consumed_length', '>', 0);
                    })
                    ->get();
                if ($cuttingConsumptions->isNotEmpty()) {
                    foreach ($cuttingConsumptions as $cCons) {
                        $cutLen = (float) ($cCons->consumed_length ?: $cCons->quantity_consumed);
                        $rate = (float) $cCons->unit_cost;
                        $rawMat = $cCons->inventoryBatch?->rawMaterial;
                        if ($rawMat && $cutLen > 0) {
                            $targetOutputs = [];
                            foreach ($batchJobs as $bj) {
                                $targetOutputs[] = [
                                    'manufacturing_product_id' => $bj->manufacturing_product_id,
                                    'planned_quantity' => $bj->target_quantity,
                                    'pattern_id' => $bj->pattern_id,
                                ];
                            }
                            $bd = \App\Services\FabricCuttingAreaService::computeCuttingBreakdown($cutLen, $rawMat, $targetOutputs, $rate);
                            $wCost = (float) ($bd['total_wastage_cost'] ?? 0);
                            if ($wCost > 0) {
                                $totalWastageCost += $wCost;
                            }
                        }
                    }
                    if ($totalWastageCost > 0) {
                        $wastageLog[] = [
                            'product_name'    => 'Shared Cutting Stage Fabric Wastage Allocation',
                            'task_name'       => 'Cutting',
                            'quantity_wasted' => 1,
                            'unit_cost'       => $totalWastageCost,
                            'total_cost'      => $totalWastageCost,
                        ];
                    }
                }
            }
        }

        // Grand total
        $totalManufacturingCost = $totalMaterialCost + $totalLaborCost + $totalWastageCost;

        // Sum finished yield across all jobs in the batch (or target quantities)
        $totalFinished = 0;
        foreach ($batchJobs as $j) {
            $q = (int) $j->total_produced_quantity;
            if ($q <= 0) {
                $q = (int) $j->target_quantity;
            }
            $totalFinished += $q;
        }
        $finishedUnits = $totalFinished > 0 ? $totalFinished : (int) ($batch->planned_quantity ?: 1);
        $averageCostPerUnit = $finishedUnits > 0 ? round($totalManufacturingCost / $finishedUnits, 2) : 0.00;

        // Labor Details
        $laborAllocations = \App\Models\JobLaborAllocation::whereIn('job_id', $jobCodes)->with(['labor', 'task'])->get();

        return [
            'total_material_cost' => $totalMaterialCost,
            'fabric_cost' => $fabricCost,
            'subsidiary_cost' => $subsidiaryCost,
            'stitching_cost' => $stitchingCost,
            'packaging_cost' => $packagingCost,
            'overhead_cost' => $overheadCost,
            'total_labor_cost' => $totalLaborCost,
            'total_wastage_cost' => $totalWastageCost,
            'total_manufacturing_cost' => $totalManufacturingCost,
            'average_cost_per_unit' => $averageCostPerUnit,
            'finished_units' => $finishedUnits,
            'labor_details' => [
                'allocations' => $laborAllocations,
            ],
            'wastage_details' => [
                'wastage_log' => $wastageLog,
            ],
        ];
    }

    /**
     * Fetch a 360-degree cost rollup breakdown for a specific production job.
     */
    public function getJobCostSummary(int $jobId): array
    {
        $job = \App\Models\ProductionJob::with(['batch', 'materialConsumptions.inventoryBatch.rawMaterial.category', 'allocations', 'wastages'])->findOrFail($jobId);
        $batchId = $job->production_batch_db_id ?: $job->batch?->id;
        $batch = $job->batch ?: \App\Models\ProductionBatch::find($batchId);

        if (!$batch && !empty($job->production_batch_id)) {
            $batch = \App\Models\ProductionBatch::where('batch_code', $job->production_batch_id)->first();
            if ($batch && !$job->production_batch_db_id) {
                $job->update(['production_batch_db_id' => $batch->id]);
            }
        }

        // Get batch summary for pro-rata apportionment
        $batchSummary = $batch ? $this->getBatchCostSummary($batch->id) : [
            'fabric_cost' => 0.0, 'subsidiary_cost' => 0.0, 'overhead_cost' => 0.0, 'stitching_cost' => 0.0, 'total_wastage_cost' => 0.0
        ];

        $batchJobs = $batch ? \App\Models\ProductionJob::where('production_batch_db_id', $batch->id)
            ->orWhere('production_batch_id', $batch->batch_code)->get() : collect([$job]);
        $totalBatchTarget = max(1, $batchJobs->sum('target_quantity'));
        $jobQty = (int) ($job->target_quantity ?: 1);
        $apportionRatio = min(1.0, $jobQty / $totalBatchTarget);

        // 1. Fabric cost (Job specific or pro-rata from batch if logged on master cutting job)
        $fabricCost = (float) $job->materialConsumptions()
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-FAB')
                       ->orWhere('code', 'like', '%FAB%')
                       ->orWhere('name', 'like', '%Fabric%')
                       ->orWhere('unit_type', 'length_based');
                })
                ->orWhereNotNull('inventory_bale_roll_id')
                ->orWhere('consumed_length', '>', 0)
                ->orWhere('total_fabric_cost', '>', 0);
            })
            ->sum('total_cost');

        if ($fabricCost === 0.0 && (float)$job->materialConsumptions()->sum('total_fabric_cost') > 0) {
            $fabricCost = (float) $job->materialConsumptions()->sum('total_fabric_cost');
        }

        if ($fabricCost === 0.0 && (float)$job->materialConsumptions()->whereNotNull('inventory_bale_roll_id')->sum('total_cost') > 0) {
            $fabricCost = (float) $job->materialConsumptions()->whereNotNull('inventory_bale_roll_id')->sum('total_cost');
        }

        if ($fabricCost === 0.0 && $batchSummary['fabric_cost'] > 0) {
            $fabricCost = round($batchSummary['fabric_cost'] * $apportionRatio, 2);
        }

        // 2. Subsidiary cost (Job specific)
        $subsidiaryCost = (float) $job->materialConsumptions()
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-SUB')
                       ->orWhere('code', 'like', '%SUB%')
                       ->orWhere('name', 'like', '%Subsidiary%')
                       ->orWhere('name', 'like', '%Trim%');
                })
                ->orWhereHas('inventoryBatch.rawMaterial', function ($rmq) {
                    $rmq->where('name', 'like', '%button%')
                        ->orWhere('name', 'like', '%zipper%')
                        ->orWhere('name', 'like', '%thread%')
                        ->orWhere('name', 'like', '%elastic%')
                        ->orWhere('name', 'like', '%label%')
                        ->orWhere('name', 'like', '%sub%');
                })
                ->orWhere(function ($subQ) {
                    $subQ->whereNull('inventory_bale_roll_id')
                         ->where(function($lq) { $lq->whereNull('consumed_length')->orWhere('consumed_length', 0); })
                         ->where(function($fq) { $fq->whereNull('total_fabric_cost')->orWhere('total_fabric_cost', 0); })
                         ->whereDoesntHave('inventoryBatch.rawMaterial.category', function ($cq) {
                             $cq->where('code', 'CAT-FAB')
                                ->orWhere('code', 'like', '%FAB%')
                                ->orWhere('name', 'like', '%Fabric%')
                                ->orWhere('unit_type', 'length_based');
                         });
                });
            })
            ->sum('total_cost');

        if ($subsidiaryCost === 0.0 && $batchSummary['subsidiary_cost'] > 0) {
            $subsidiaryCost = round($batchSummary['subsidiary_cost'] * $apportionRatio, 2);
        }

        // 3. Packaging cost (Job specific)
        $packagingCost = (float) $job->materialConsumptions()
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-PKG')->orWhere('code', 'like', '%PKG%'))
            ->sum('total_cost');

        // 4. General Overheads (Apportioned)
        $overheadCost = round($batchSummary['overhead_cost'] * $apportionRatio, 2);

        // 5. Stitching cost (Apportioned)
        $stitchingCost = round($batchSummary['stitching_cost'] * $apportionRatio, 2);

        $totalMaterialCost = $fabricCost + $subsidiaryCost + $stitchingCost + $packagingCost + $overheadCost;

        // 6. Labor Wages (Job specific)
        $totalLaborCost = (float) $job->allocations()->sum('calculated_wage');

        // 7. Wastage Log (Job specific or pro-rata if logged on shared roll)
        $defaultJobFabricRate = (float) $job->materialConsumptions()
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-FAB'))
            ->avg('unit_cost') ?: 150.00;

        $wastageLog = [];
        $wastages = $job->wastages()->with(['manufacturingProduct', 'task', 'inventoryBaleRoll.bale'])->get();

        if ($wastages->isEmpty() && $batchSummary['total_wastage_cost'] > 0) {
            if (!empty($batchSummary['wastage_details']['wastage_log'])) {
                foreach ($batchSummary['wastage_details']['wastage_log'] as $bW) {
                    $apportionedWastageCost = round($bW['total_cost'] * $apportionRatio, 2);
                    $wastageLog[] = [
                        'product_name'    => $bW['product_name'] . ' (Apportioned)',
                        'task_name'       => $bW['task_name'],
                        'quantity_wasted' => round($bW['quantity_wasted'] * $apportionRatio, 2),
                        'unit_cost'       => $bW['unit_cost'],
                        'total_cost'      => $apportionedWastageCost,
                    ];
                }
            } else {
                $allocatedWastageCost = round($batchSummary['total_wastage_cost'] * $apportionRatio, 2);
                $wastageLog[] = [
                    'product_name'    => 'Shared Cutting Stage Wastage Allocation',
                    'task_name'       => 'Cutting',
                    'quantity_wasted' => 1,
                    'unit_cost'       => $allocatedWastageCost,
                    'total_cost'      => $allocatedWastageCost,
                ];
            }
        } else {
            foreach ($wastages as $w) {
                $unitCost = $defaultJobFabricRate;
                if ($w->inventoryBaleRoll?->bale?->unit_cost) {
                    $unitCost = (float) $w->inventoryBaleRoll->bale->unit_cost;
                }

                $qty = (float) $w->quantity_wasted;
                $wCost = round($qty * $unitCost, 2);

                $reason = trim($w->reason ?? '');
                $prodName = $w->manufacturingProduct?->name;

                if (!empty($reason)) {
                    $title = $reason;
                    if ($prodName && !str_contains(strtolower($reason), strtolower($prodName))) {
                        $title .= " ({$prodName})";
                    }
                } else {
                    $title = $prodName ? "Defective Piece - {$prodName}" : "Damaged Material / Scrap";
                }

                $wastageLog[] = [
                    'product_name'    => $title,
                    'task_name'       => $w->task?->name ?? 'Production',
                    'quantity_wasted' => $qty,
                    'unit_cost'       => $unitCost,
                    'total_cost'      => $wCost,
                ];
            }
        }

        $totalWastageCost = array_sum(array_column($wastageLog, 'total_cost'));
        if ($totalWastageCost === 0.0 && (float) $job->materialConsumptions()->sum('allocated_wastage_cost') > 0) {
            $allocatedWastageCost = (float) $job->materialConsumptions()->sum('allocated_wastage_cost');
            $totalWastageCost = $allocatedWastageCost;
            $wastageLog[] = [
                'product_name'    => 'Shared Cutting Stage Fabric Wastage Allocation',
                'task_name'       => 'Cutting',
                'quantity_wasted' => 1,
                'unit_cost'       => $allocatedWastageCost,
                'total_cost'      => $allocatedWastageCost,
            ];
        }

        if ($totalWastageCost === 0.0 && (float) \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)->sum('allocated_wastage_cost') > 0) {
            $allocatedWastageCost = round((float) \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)->sum('allocated_wastage_cost') * $apportionRatio, 2);
            $totalWastageCost = $allocatedWastageCost;
            $wastageLog[] = [
                'product_name'    => 'Shared Cutting Stage Fabric Wastage Allocation',
                'task_name'       => 'Cutting',
                'quantity_wasted' => 1,
                'unit_cost'       => $allocatedWastageCost,
                'total_cost'      => $allocatedWastageCost,
            ];
        }

        if ($totalWastageCost === 0.0) {
            $cuttingConsumptions = \App\Models\JobMaterialConsumption::whereIn('production_job_id', $jobIds)
                ->where(function($q) {
                    $q->whereNotNull('inventory_bale_roll_id')->orWhere('consumed_length', '>', 0);
                })
                ->get();
            if ($cuttingConsumptions->isNotEmpty()) {
                foreach ($cuttingConsumptions as $cCons) {
                    $cutLen = (float) ($cCons->consumed_length ?: $cCons->quantity_consumed);
                    $rate = (float) $cCons->unit_cost;
                    $rawMat = $cCons->inventoryBatch?->rawMaterial;
                    if ($rawMat && $cutLen > 0) {
                        $targetOutputs = [
                            [
                                'manufacturing_product_id' => $job->manufacturing_product_id,
                                'planned_quantity' => $job->target_quantity,
                                'pattern_id' => $job->pattern_id,
                            ]
                        ];
                        $bd = \App\Services\FabricCuttingAreaService::computeCuttingBreakdown($cutLen, $rawMat, $targetOutputs, $rate);
                        $wCost = (float) ($bd['total_wastage_cost'] ?? 0);
                        if ($wCost > 0) {
                            $totalWastageCost += round($wCost * $apportionRatio, 2);
                        }
                    }
                }
                if ($totalWastageCost > 0) {
                    $wastageLog[] = [
                        'product_name'    => 'Shared Cutting Stage Fabric Wastage Allocation',
                        'task_name'       => 'Cutting',
                        'quantity_wasted' => 1,
                        'unit_cost'       => $totalWastageCost,
                        'total_cost'      => $totalWastageCost,
                    ];
                }
            }
        }

        $totalManufacturingCost = $totalMaterialCost + $totalLaborCost + $totalWastageCost;

        $finishedUnits = (int) $job->total_produced_quantity;
        if ($finishedUnits <= 0) $finishedUnits = (int) $job->target_quantity;
        $averageCostPerUnit = $finishedUnits > 0 ? round($totalManufacturingCost / $finishedUnits, 2) : 0.00;

        $laborAllocations = $job->allocations()->with(['labor', 'task'])->get();

        // --- ITEMIZED MATERIAL DETAILS COMPUTATION FOR ALLOCATION BASIS COLUMN ---
        // 1. Fabric Details Summary
        $fabricConsumptions = $job->materialConsumptions()
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-FAB')
                       ->orWhere('code', 'like', '%FAB%')
                       ->orWhere('name', 'like', '%Fabric%')
                       ->orWhere('unit_type', 'length_based');
                })
                ->orWhereNotNull('inventory_bale_roll_id')
                ->orWhere('consumed_length', '>', 0)
                ->orWhere('total_fabric_cost', '>', 0);
            })
            ->with(['inventoryBatch.rawMaterial', 'inventoryBaleRoll.bale.rawMaterial'])
            ->get();

        $fabricDetailParts = [];
        foreach ($fabricConsumptions as $fc) {
            $rawMat = $fc->inventoryBatch?->rawMaterial ?? $fc->inventoryBaleRoll?->bale?->rawMaterial;
            if ($rawMat) {
                $name = $rawMat->name;
                $widthStr = $rawMat->standard_width ? " ({$rawMat->standard_width} " . ($rawMat->width_unit ?? 'Inch') . ")" : '';
                $qty = (float) ($fc->consumed_length ?: $fc->quantity_consumed);
                $unit = $rawMat->unit ?? 'M';
                if (in_array(strtolower($unit), ['meters', 'meter', 'm'])) {
                    $unit = 'M';
                }
                $qtyFormatted = (floor($qty) == $qty) ? number_format($qty, 0) : number_format($qty, 2);
                $fabricDetailParts[] = "{$name}{$widthStr} {$qtyFormatted} {$unit}";
            }
        }

        if (empty($fabricDetailParts) && $fabricCost > 0) {
            $batchJobIds = $batchJobs->pluck('id');
            $batchFabrics = JobMaterialConsumption::whereIn('production_job_id', $batchJobIds)
                ->where(function ($q) {
                    $q->whereHas('inventoryBatch.rawMaterial.category', fn($cq) => $cq->where('code', 'CAT-FAB')->orWhere('code', 'like', '%FAB%')->orWhere('name', 'like', '%Fabric%'))
                      ->orWhereNotNull('inventory_bale_roll_id')
                      ->orWhere('consumed_length', '>', 0);
                })
                ->with(['inventoryBatch.rawMaterial', 'inventoryBaleRoll.bale.rawMaterial'])
                ->get();

            foreach ($batchFabrics as $bfc) {
                $rawMat = $bfc->inventoryBatch?->rawMaterial ?? $bfc->inventoryBaleRoll?->bale?->rawMaterial;
                if ($rawMat) {
                    $name = $rawMat->name;
                    $widthStr = $rawMat->standard_width ? " ({$rawMat->standard_width} " . ($rawMat->width_unit ?? 'Inch') . ")" : '';
                    $totQty = (float) ($bfc->consumed_length ?: $bfc->quantity_consumed);
                    $appQty = round($totQty * $apportionRatio, 2);
                    $unit = in_array(strtolower($rawMat->unit ?? 'M'), ['meters', 'meter', 'm']) ? 'M' : ($rawMat->unit ?? 'M');
                    $qtyFormatted = (floor($appQty) == $appQty) ? number_format($appQty, 0) : number_format($appQty, 2);
                    $fabricDetailParts[] = "{$name}{$widthStr} {$qtyFormatted} {$unit}";
                }
            }
        }

        $fabricDetailsText = !empty($fabricDetailParts)
            ? implode(', ', array_unique($fabricDetailParts))
            : ($fabricCost > 0 ? "Raw fabric consumed for production" : "No raw fabric consumed");

        // 2. Shared Cutting Wastage Details Summary
        if ($totalWastageCost > 0) {
            $avgFabricRate = (float) $job->materialConsumptions()
                ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-FAB'))
                ->avg('unit_cost') ?: ($defaultJobFabricRate ?: 150.00);

            $wastageQty = round($totalWastageCost / max(1, $avgFabricRate), 2);
            $qtyFormatted = (floor($wastageQty) == $wastageQty) ? number_format($wastageQty, 0) : number_format($wastageQty, 2);
            $wastageDetailText = $wastageQty > 0 ? "{$qtyFormatted} M (Area-weighted waste allocation)" : "Area-weighted waste allocation";
        } else {
            $wastageDetailText = "0.00 M (No cutting wastage)";
        }

        // 3. Subsidiary Material Details Summary
        $subConsumptions = $job->materialConsumptions()
            ->where(function ($q) {
                $q->whereHas('inventoryBatch.rawMaterial.category', function ($cq) {
                    $cq->where('code', 'CAT-SUB')
                       ->orWhere('code', 'like', '%SUB%')
                       ->orWhere('name', 'like', '%Subsidiary%')
                       ->orWhere('name', 'like', '%Trim%');
                })
                ->orWhereHas('inventoryBatch.rawMaterial', function ($rmq) {
                    $rmq->where('name', 'like', '%button%')
                        ->orWhere('name', 'like', '%zipper%')
                        ->orWhere('name', 'like', '%thread%')
                        ->orWhere('name', 'like', '%elastic%')
                        ->orWhere('name', 'like', '%label%')
                        ->orWhere('name', 'like', '%sub%');
                })
                ->orWhere(function ($subQ) {
                    $subQ->whereNull('inventory_bale_roll_id')
                         ->where(function($lq) { $lq->whereNull('consumed_length')->orWhere('consumed_length', 0); })
                         ->where(function($fq) { $fq->whereNull('total_fabric_cost')->orWhere('total_fabric_cost', 0); })
                         ->whereDoesntHave('inventoryBatch.rawMaterial.category', function ($cq) {
                             $cq->where('code', 'CAT-FAB')
                                ->orWhere('code', 'like', '%FAB%')
                                ->orWhere('name', 'like', '%Fabric%')
                                ->orWhere('unit_type', 'length_based');
                         });
                });
            })
            ->with('inventoryBatch.rawMaterial')
            ->get();

        $subDetailParts = [];
        foreach ($subConsumptions as $sc) {
            $rawMat = $sc->inventoryBatch?->rawMaterial;
            if ($rawMat) {
                $qty = (float) $sc->quantity_consumed;
                $unit = $rawMat->unit ?? 'Pcs';
                $qtyFormatted = (floor($qty) == $qty) ? number_format($qty, 0) : number_format($qty, 2);
                $subDetailParts[] = "{$rawMat->name} {$qtyFormatted} {$unit}";
            }
        }

        if (empty($subDetailParts) && $subsidiaryCost > 0) {
            $batchSubConsumptions = JobMaterialConsumption::whereIn('production_job_id', $batchJobs->pluck('id'))
                ->whereHas('inventoryBatch.rawMaterial.category', fn($cq) => $cq->where('code', 'CAT-SUB')->orWhere('code', 'like', '%SUB%')->orWhere('name', 'like', '%Subsidiary%'))
                ->with('inventoryBatch.rawMaterial')
                ->get();
            foreach ($batchSubConsumptions as $bsc) {
                $rawMat = $bsc->inventoryBatch?->rawMaterial;
                if ($rawMat) {
                    $appQty = round((float) $bsc->quantity_consumed * $apportionRatio, 2);
                    $unit = $rawMat->unit ?? 'Pcs';
                    $qtyFormatted = (floor($appQty) == $appQty) ? number_format($appQty, 0) : number_format($appQty, 2);
                    $subDetailParts[] = "{$rawMat->name} {$qtyFormatted} {$unit}";
                }
            }
        }

        $subsidiaryDetailsText = !empty($subDetailParts)
            ? implode(', ', array_unique($subDetailParts))
            : ($subsidiaryCost > 0 ? "Trims, elastic, threads, labels" : "No subsidiary materials used");

        // 4. Labor & Stitching Details Summary
        $laborWorkers = $laborAllocations->map(function($alloc) {
            $name = $alloc->labor?->name ?? 'Worker';
            $wage = number_format($alloc->calculated_wage, 2);
            return "{$name} (₹{$wage})";
        })->filter()->unique()->values()->all();

        $laborSummaryText = !empty($laborWorkers)
            ? implode(', ', $laborWorkers)
            : "Direct labor wages (₹" . number_format($totalLaborCost, 2) . ")";
        $laborDetailsText = "{$laborSummaryText} & Stitching pool (₹" . number_format($stitchingCost, 2) . ")";

        // 5. Packaging Details Summary
        $pkgConsumptions = $job->materialConsumptions()
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-PKG')->orWhere('code', 'like', '%PKG%'))
            ->with('inventoryBatch.rawMaterial')
            ->get();
        $pkgDetailParts = [];
        foreach ($pkgConsumptions as $pkc) {
            $rawMat = $pkc->inventoryBatch?->rawMaterial;
            if ($rawMat) {
                $qty = (float) $pkc->quantity_consumed;
                $unit = $rawMat->unit ?? 'Pcs';
                $qtyFormatted = (floor($qty) == $qty) ? number_format($qty, 0) : number_format($qty, 2);
                $pkgDetailParts[] = "{$rawMat->name} {$qtyFormatted} {$unit}";
            }
        }
        $packagingDetailsText = !empty($pkgDetailParts) ? implode(', ', array_unique($pkgDetailParts)) : ($packagingCost > 0 ? "Packing bags, boxes, labels" : "No packaging materials used");

        // 6. Overhead Details Summary
        $ohdConsumptions = $job->materialConsumptions()
            ->whereHas('inventoryBatch.rawMaterial.category', fn($q) => $q->where('code', 'CAT-OHD')->orWhere('code', 'like', '%OHD%'))
            ->with('inventoryBatch.rawMaterial')
            ->get();
        $ohdDetailParts = [];
        foreach ($ohdConsumptions as $ohc) {
            $rawMat = $ohc->inventoryBatch?->rawMaterial;
            if ($rawMat) {
                $qty = (float) $ohc->quantity_consumed;
                $unit = $rawMat->unit ?? 'Units';
                $qtyFormatted = (floor($qty) == $qty) ? number_format($qty, 0) : number_format($qty, 2);
                $ohdDetailParts[] = "{$rawMat->name} {$qtyFormatted} {$unit}";
            }
        }
        $overheadDetailsText = !empty($ohdDetailParts) ? implode(', ', array_unique($ohdDetailParts)) : ($overheadCost > 0 ? "General consumables & factory overheads" : "No overheads allocated");

        return [
            'total_material_cost' => $totalMaterialCost,
            'fabric_cost' => $fabricCost,
            'subsidiary_cost' => $subsidiaryCost,
            'stitching_cost' => $stitchingCost,
            'packaging_cost' => $packagingCost,
            'overhead_cost' => $overheadCost,
            'total_labor_cost' => $totalLaborCost,
            'total_wastage_cost' => $totalWastageCost,
            'total_manufacturing_cost' => $totalManufacturingCost,
            'average_cost_per_unit' => $averageCostPerUnit,
            'finished_units' => $finishedUnits,
            'fabric_details' => [
                'summary_text' => $fabricDetailsText,
            ],
            'wastage_details' => [
                'summary_text' => $wastageDetailText,
                'wastage_log'  => $wastageLog,
            ],
            'subsidiary_details' => [
                'summary_text' => $subsidiaryDetailsText,
            ],
            'labor_details' => [
                'summary_text' => $laborDetailsText,
                'allocations'  => $laborAllocations,
            ],
            'packaging_details' => [
                'summary_text' => $packagingDetailsText,
            ],
            'overhead_details' => [
                'summary_text' => $overheadDetailsText,
            ],
        ];
    }

    /**
     * Cache batch costing metrics directly in production_batches columns.
     */
    public function cacheBatchCostSummary(int $batchId): void
    {
        $batch = ProductionBatch::findOrFail($batchId);
        $summary = $this->getBatchCostSummary($batchId);

        $batch->update([
            'total_material_cost' => $summary['total_material_cost'],
            'total_labor_cost' => $summary['total_labor_cost'],
            'total_wastage_cost' => $summary['total_wastage_cost'],
            'total_manufacturing_cost' => $summary['total_manufacturing_cost'],
            'average_unit_cost' => $summary['average_cost_per_unit'],
        ]);
    }

    /**
     * Allocate General Overheads/Consumables (CAT-OHD) to a batch.
     */
    public function allocateOverheadCost(ProductionBatch $batch, array $overheadItems): void
    {
        DB::transaction(function () use ($batch, $overheadItems) {
            foreach ($overheadItems as $item) {
                $rawMaterialId = $item['raw_material_id'];
                $qtyToAllocate = (float) $item['allocated_quantity'];
                $method = $item['allocation_method'] ?? 'direct_batch';

                if ($qtyToAllocate <= 0) {
                    continue;
                }

                // Find active batches for the overhead material using FIFO
                $batches = InventoryBatch::active()
                    ->byMaterial($rawMaterialId)
                    ->orderBy('purchase_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $remaining = $qtyToAllocate;
                foreach ($batches as $invBatch) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $deduct = min($remaining, (float) $invBatch->balance_quantity);
                    $invBatch->deductQuantity($deduct);

                    $allocatedCost = $deduct * (float) ($invBatch->purchase_rate ?: $invBatch->unit_cost);

                    // Create overhead cost allocation log
                    DB::table('overhead_cost_allocations')->insert([
                        'production_batch_id' => $batch->id,
                        'raw_material_id' => $rawMaterialId,
                        'inventory_batch_id' => $invBatch->id,
                        'allocated_quantity' => $deduct,
                        'allocated_cost' => $allocatedCost,
                        'allocation_method' => $method,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $remaining -= $deduct;
                }

                if ($remaining > 0) {
                    throw new \Exception("Insufficient inventory to allocate overhead for raw material ID: {$rawMaterialId}. Shortage: {$remaining}");
                }
            }

            // Recalculate batch cost summary
            $this->cacheBatchCostSummary($batch->id);
        });
    }
}
