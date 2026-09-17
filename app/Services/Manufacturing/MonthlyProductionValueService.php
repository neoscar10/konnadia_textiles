<?php

namespace App\Services\Manufacturing;

use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsBatchPackaging;
use App\Models\JobLaborAllocation;
use App\Models\JobMaterialConsumption;
use App\Models\JobWastage;
use App\Models\Labor;
use App\Models\ProductionJob;
use App\Models\RawMaterial;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonthlyProductionValueService
{
    /**
     * Calculate the complete monthly production value for a given year and month.
     *
     * Total Monthly Production Value =
     *   Sum of Completed Jobs Cost (Fabric + Trims/Subsidiary + Wastage + Piece-Rate Labor)
     *   + Sum of Storefront Product Packaging Costs
     *
     * Salaried staff labor allocations on jobs are explicitly excluded because salaried staff
     * payroll is already accounted for in Pillar 2 of Monthly Overheads.
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function calculate(int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate   = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Process Completed Jobs in the period
        $jobsData = $this->calculateCompletedJobsValue($startDate, $endDate);

        // 2. Process Storefront Packaging Materials Converted in the period
        $packagingData = $this->calculateStorefrontPackagingValue($startDate, $endDate);

        // 3. Grand Total Monthly Production Value
        $totalProductionValue = round($jobsData['total_eligible_jobs_cost'] + $packagingData['total_packaging_cost'], 2);

        return [
            'year'                         => $year,
            'month'                        => $month,
            'start_date'                   => $startDate->format('Y-m-d'),
            'end_date'                     => $endDate->format('Y-m-d'),
            'total_production_value'       => $totalProductionValue,
            'total_jobs_cost'              => $jobsData['total_eligible_jobs_cost'],
            'total_packaging_cost'         => $packagingData['total_packaging_cost'],
            'total_fabric_cost'            => $jobsData['total_fabric_cost'],
            'total_subsidiary_cost'        => $jobsData['total_subsidiary_cost'],
            'total_wastage_cost'           => $jobsData['total_wastage_cost'],
            'total_piece_rate_labor'       => $jobsData['total_piece_rate_labor'],
            'total_salaried_labor_excluded'=> $jobsData['total_salaried_labor_excluded'],
            'total_raw_labor_cost'         => $jobsData['total_raw_labor_cost'],
            'completed_jobs_count'         => $jobsData['completed_jobs_count'],
            'conversions_count'            => $packagingData['conversions_count'],
            'jobs_breakdown'               => $jobsData['jobs_breakdown'],
            'packaging_breakdown'          => $packagingData['packaging_breakdown'],
        ];
    }

    /**
     * Compute production costing across all completed jobs in the date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function calculateCompletedJobsValue(Carbon $startDate, Carbon $endDate): array
    {
        // Query completed jobs in the given date window (checking job_date, or fallback to updated_at)
        $completedJobs = ProductionJob::with([
            'batch',
            'manufacturingProduct',
            'materialConsumptions.inventoryBatch.rawMaterial.category',
            'allocations.labor',
            'wastages.inventoryBaleRoll.bale',
        ])
        ->where('status', 'completed')
        ->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('job_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->whereNull('job_date')
                       ->whereBetween('updated_at', [$startDate, $endDate]);
              });
        })
        ->get();

        $totalFabricCost       = 0.0;
        $totalSubsidiaryCost   = 0.0;
        $totalWastageCost      = 0.0;
        $totalPieceRateLabor   = 0.0;
        $totalSalariedExcluded = 0.0;
        $totalRawLaborCost     = 0.0;
        $totalEligibleJobsCost = 0.0;

        $jobsBreakdown = [];

        foreach ($completedJobs as $job) {
            $jobCosting = $this->calculateSingleJobCosting($job);

            $totalFabricCost       += $jobCosting['fabric_cost'];
            $totalSubsidiaryCost   += $jobCosting['subsidiary_cost'];
            $totalWastageCost      += $jobCosting['wastage_cost'];
            $totalPieceRateLabor   += $jobCosting['piece_rate_labor'];
            $totalSalariedExcluded += $jobCosting['salaried_labor_excluded'];
            $totalRawLaborCost     += $jobCosting['total_labor'];
            $totalEligibleJobsCost += $jobCosting['eligible_cost'];

            $jobsBreakdown[] = [
                'job_id'                  => $job->id,
                'job_code'                => $job->job_code,
                'product_name'            => $job->manufacturingProduct?->name ?? 'Production Job #' . $job->id,
                'job_date'                => $job->job_date?->format('Y-m-d') ?? $job->updated_at?->format('Y-m-d'),
                'produced_quantity'       => (int) ($job->total_produced_quantity ?: $job->target_quantity),
                'fabric_cost'             => $jobCosting['fabric_cost'],
                'subsidiary_cost'         => $jobCosting['subsidiary_cost'],
                'wastage_cost'            => $jobCosting['wastage_cost'],
                'piece_rate_labor'        => $jobCosting['piece_rate_labor'],
                'salaried_labor_excluded' => $jobCosting['salaried_labor_excluded'],
                'total_labor'             => $jobCosting['total_labor'],
                'eligible_cost'           => $jobCosting['eligible_cost'],
            ];
        }

        return [
            'completed_jobs_count'         => $completedJobs->count(),
            'total_fabric_cost'            => round($totalFabricCost, 2),
            'total_subsidiary_cost'        => round($totalSubsidiaryCost, 2),
            'total_wastage_cost'           => round($totalWastageCost, 2),
            'total_piece_rate_labor'       => round($totalPieceRateLabor, 2),
            'total_salaried_labor_excluded'=> round($totalSalariedExcluded, 2),
            'total_raw_labor_cost'         => round($totalRawLaborCost, 2),
            'total_eligible_jobs_cost'     => round($totalEligibleJobsCost, 2),
            'jobs_breakdown'               => $jobsBreakdown,
        ];
    }

    /**
     * Compute costing for a single completed production job.
     *
     * @param ProductionJob $job
     * @return array
     */
    public function calculateSingleJobCosting(ProductionJob $job): array
    {
        // 1. Direct Fabric Cost
        $fabricCost = (float) $job->materialConsumptions
            ->filter(function ($c) {
                $category = $c->inventoryBatch?->rawMaterial?->category;
                $catCode  = strtoupper($category?->code ?? '');
                $catName  = strtoupper($category?->name ?? '');

                return str_contains($catCode, 'FAB')
                    || str_contains($catName, 'FABRIC')
                    || $category?->unit_type === 'length_based'
                    || !empty($c->inventory_bale_roll_id)
                    || (float) $c->consumed_length > 0
                    || (float) $c->total_fabric_cost > 0;
            })
            ->sum('total_cost');

        if ($fabricCost === 0.0) {
            $fabricCost = (float) $job->materialConsumptions->sum('total_fabric_cost');
        }

        // 2. Direct Subsidiary Cost (buttons, zippers, threads, trims)
        $subsidiaryCost = (float) $job->materialConsumptions
            ->filter(function ($c) {
                $rawMat   = $c->inventoryBatch?->rawMaterial;
                $category = $rawMat?->category;
                $catCode  = strtoupper($category?->code ?? '');
                $catName  = strtoupper($category?->name ?? '');
                $matName  = strtolower($rawMat?->name ?? '');

                $isExplicitSub = str_contains($catCode, 'SUB')
                    || str_contains($catName, 'SUBSIDIARY')
                    || str_contains($catName, 'TRIM')
                    || str_contains($matName, 'button')
                    || str_contains($matName, 'zipper')
                    || str_contains($matName, 'thread')
                    || str_contains($matName, 'elastic')
                    || str_contains($matName, 'label');

                $isNotFabricOrOverhead = empty($c->inventory_bale_roll_id)
                    && (float) $c->consumed_length <= 0
                    && (float) $c->total_fabric_cost <= 0
                    && !str_contains($catCode, 'FAB')
                    && !str_contains($catCode, 'PKG')
                    && !str_contains($catCode, 'OHD');

                return $isExplicitSub || $isNotFabricOrOverhead;
            })
            ->sum('total_cost');

        // 3. Wastage Cost (Job direct wastages or allocated cutting wastage)
        $defaultFabricRate = (float) $job->materialConsumptions
            ->filter(fn($c) => str_contains(strtoupper($c->inventoryBatch?->rawMaterial?->category?->code ?? ''), 'FAB'))
            ->avg('unit_cost') ?: 150.00;

        $wastageCost = 0.0;
        foreach ($job->wastages as $w) {
            $unitCost = (float) ($w->inventoryBaleRoll?->bale?->unit_cost ?: $defaultFabricRate);
            $wastageCost += round(((float) $w->quantity_wasted) * $unitCost, 2);
        }

        if ($wastageCost === 0.0) {
            $wastageCost = (float) $job->materialConsumptions->sum('allocated_wastage_cost');
        }

        // 4. Labor Cost Breakdown (Piece-Rate vs Salaried Staff)
        $pieceRateLabor   = 0.0;
        $salariedExcluded = 0.0;

        // Eager load or fetch allocations
        $allocations = $job->allocations;
        if ($allocations->isEmpty() && !empty($job->job_code)) {
            $allocations = JobLaborAllocation::with('labor')->where('job_id', $job->job_code)->get();
        }

        foreach ($allocations as $alloc) {
            $wage  = (float) $alloc->calculated_wage;
            $labor = $alloc->labor;

            // Check if worker is salaried
            $isSalaried = false;
            if ($labor) {
                $isSalaried = ($labor->payment_method === 'salary')
                    || ((float) $labor->monthly_salary > 0);
            }

            if ($isSalaried) {
                $salariedExcluded += $wage;
            } else {
                $pieceRateLabor += $wage;
            }
        }

        $totalLabor = $pieceRateLabor + $salariedExcluded;

        // Eligible job cost includes only piece-rate labor
        $eligibleCost = round($fabricCost + $subsidiaryCost + $wastageCost + $pieceRateLabor, 2);

        return [
            'fabric_cost'             => round($fabricCost, 2),
            'subsidiary_cost'         => round($subsidiaryCost, 2),
            'wastage_cost'            => round($wastageCost, 2),
            'piece_rate_labor'        => round($pieceRateLabor, 2),
            'salaried_labor_excluded' => round($salariedExcluded, 2),
            'total_labor'             => round($totalLabor, 2),
            'eligible_cost'           => $eligibleCost,
        ];
    }

    /**
     * Compute packaging costs from all storefront product conversions in the period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function calculateStorefrontPackagingValue(Carbon $startDate, Carbon $endDate): array
    {
        $fgBatches = FinishedGoodsBatch::with([
            'frontEndProduct',
            'packagingDeductions.rawMaterial.category',
        ])
        ->whereBetween('converted_date', [$startDate, $endDate])
        ->get();

        $totalPackagingCost = 0.0;
        $packagingBreakdown = [];

        foreach ($fgBatches as $batch) {
            $batchPkgCost = 0.0;
            $items = [];

            foreach ($batch->packagingDeductions as $deduction) {
                $qty = (float) $deduction->quantity_deducted;
                $rawMat = $deduction->rawMaterial;

                $unitCost = $this->determinePackagingUnitCost($rawMat);
                $lineCost = round($qty * $unitCost, 2);

                $batchPkgCost += $lineCost;
                $items[] = [
                    'raw_material_id'   => $deduction->raw_material_id,
                    'raw_material_name' => $rawMat?->name ?? 'Packaging Material #' . $deduction->raw_material_id,
                    'quantity_deducted' => $qty,
                    'unit_cost'         => $unitCost,
                    'line_cost'         => $lineCost,
                ];
            }

            $totalPackagingCost += $batchPkgCost;

            $packagingBreakdown[] = [
                'batch_id'           => $batch->id,
                'barcode'            => $batch->barcode,
                'product_name'       => $batch->frontEndProduct?->name ?? 'FrontEnd Product #' . $batch->front_end_product_id,
                'converted_qty'      => (int) $batch->converted_qty,
                'converted_date'     => $batch->converted_date?->format('Y-m-d H:i:s'),
                'packaging_cost'     => round($batchPkgCost, 2),
                'packaging_items'    => $items,
            ];
        }

        return [
            'conversions_count'    => $fgBatches->count(),
            'total_packaging_cost' => round($totalPackagingCost, 2),
            'packaging_breakdown'  => $packagingBreakdown,
        ];
    }

    /**
     * Determine the effective unit cost of a packaging raw material.
     *
     * @param RawMaterial|null $rawMat
     * @return float
     */
    protected function determinePackagingUnitCost(?RawMaterial $rawMat): float
    {
        if (!$rawMat) {
            return 10.00;
        }

        // 1. Check latest purchase or unit cost from InventoryBatch
        $batchCost = DB::table('inventory_batches')
            ->where('raw_material_id', $rawMat->id)
            ->where(function ($q) {
                $q->where('unit_cost', '>', 0)->orWhere('purchase_rate', '>', 0);
            })
            ->latest('id')
            ->value(DB::raw('COALESCE(unit_cost, purchase_rate)'));

        if ($batchCost && (float) $batchCost > 0) {
            return (float) $batchCost;
        }

        // 2. Check RawMaterial default attributes
        if (!empty($rawMat->unit_cost) && (float) $rawMat->unit_cost > 0) {
            return (float) $rawMat->unit_cost;
        }

        if (!empty($rawMat->purchase_rate) && (float) $rawMat->purchase_rate > 0) {
            return (float) $rawMat->purchase_rate;
        }

        // Fallback default
        return 10.00;
    }
}
