<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinishedGoodsBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode',
        'front_end_product_id',
        'design_id',
        'converted_qty',
        'unit',
        'unit_factor',
        'converted_date',
        'is_published',
        'created_by',
        'costing_summary',
        'notes',
    ];

    protected $casts = [
        'converted_qty' => 'integer',
        'unit_factor' => 'integer',
        'converted_date' => 'datetime',
        'is_published' => 'boolean',
        'costing_summary' => 'array',
    ];

    public function frontEndProduct()
    {
        return $this->belongsTo(FrontEndProduct::class, 'front_end_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(FinishedGoodsBatchItem::class, 'finished_goods_batch_id');
    }

    public function packagingDeductions()
    {
        return $this->hasMany(FinishedGoodsBatchPackaging::class, 'finished_goods_batch_id');
    }

    /**
     * Get live or persisted unit costing breakdown for this converted batch.
     */
    public function getEffectiveCostingSummaryAttribute(): array
    {
        $dynamic = $this->calculateDynamicCostingSummary();
        if (!empty($dynamic) && ($dynamic['raw_values']['total'] ?? 0) > 0) {
            return $dynamic;
        }

        if (!empty($this->costing_summary) && is_array($this->costing_summary)) {
            return array_merge([
                'fabricCost'     => '₹0.00',
                'subsidiaryCost' => '₹0.00',
                'laborCost'      => '₹0.00',
                'packagingCost'  => '₹0.00',
                'wastageCost'    => '₹0.00',
                'totalUnitCost'  => '₹0.00',
                'is_dynamic'     => false,
            ], $this->costing_summary);
        }

        return [
            'fabricCost'     => '₹0.00',
            'subsidiaryCost' => '₹0.00',
            'laborCost'      => '₹0.00',
            'packagingCost'  => '₹0.00',
            'wastageCost'    => '₹0.00',
            'totalUnitCost'  => '₹0.00',
            'is_dynamic'     => false,
            'raw_values'     => [
                'fabric'     => 0.0,
                'subsidiary' => 0.0,
                'labor'      => 0.0,
                'packaging'  => 0.0,
                'wastage'    => 0.0,
                'total'      => 0.0,
            ],
        ];
    }

    /**
     * Compute actual dynamic unit cost breakdown based on constituent jobs and packaging.
     */
    public function calculateDynamicCostingSummary(): array
    {
        $targetQty = max(1, (int) ($this->converted_qty ?: 1));

        $totalFabric     = 0.0;
        $totalSubsidiary = 0.0;
        $totalLabor      = 0.0;
        $totalWastage    = 0.0;

        $items = $this->items()->with(['productionJob', 'productionBatch'])->get();
        $costingService = app(\App\Services\Manufacturing\ProductionCostingService::class);

        foreach ($items as $item) {
            $qtyUsed = (int) $item->quantity_used;
            if ($qtyUsed <= 0) continue;

            $jobSummary = null;
            $producedUnits = 1;

            if ($item->production_job_id || $item->productionJob) {
                $jobId = $item->production_job_id ?: $item->productionJob->id;
                try {
                    $jobSummary = $costingService->getJobCostSummary($jobId);
                    $producedUnits = max(1, (int) ($jobSummary['finished_units'] ?? $item->productionJob?->total_produced_quantity ?: 1));
                } catch (\Throwable $e) {
                    $jobSummary = null;
                }
            } elseif ($item->production_batch_id || $item->productionBatch) {
                $batchId = $item->production_batch_id ?: $item->productionBatch->id;
                try {
                    $jobSummary = $costingService->getBatchCostSummary($batchId);
                    $producedUnits = max(1, (int) ($jobSummary['finished_units'] ?? 1));
                } catch (\Throwable $e) {
                    $jobSummary = null;
                }
            }

            $jobRecord = $item->productionJob ?: ($item->production_job_id ? \App\Models\ProductionJob::find($item->production_job_id) : null);
            $ratio = $qtyUsed / max(1, $producedUnits);

            $jobFabric = (float) ($jobSummary['fabric_cost'] ?? 0);
            if ($jobFabric <= 0 && $jobRecord?->cutting_details) {
                $jobFabric = (float) ($jobRecord->cutting_details['fabric_consumption_cost'] ?? 0);
            }

            $jobSubsidiary = (float) ($jobSummary['subsidiary_cost'] ?? 0);
            if ($jobSubsidiary <= 0 && is_array($jobRecord?->subsidiary_usage)) {
                $jobSubsidiary = (float) array_sum(array_column($jobRecord->subsidiary_usage, 'cost'));
            }

            $jobLabor = (float) ($jobSummary['total_labor_cost'] ?? 0);

            $jobWastage = (float) ($jobSummary['total_wastage_cost'] ?? 0);
            if ($jobWastage <= 0 && $jobRecord?->cutting_details) {
                $jobWastage = (float) ($jobRecord->cutting_details['wastage_cost'] ?? 0);
            }

            $totalFabric     += $jobFabric * $ratio;
            $totalSubsidiary += $jobSubsidiary * $ratio;
            $totalLabor      += $jobLabor * $ratio;
            $totalWastage    += $jobWastage * $ratio;
        }

        // Packaging Material Deductions
        $totalPackaging = 0.0;
        $packagings = $this->packagingDeductions()->with('rawMaterial')->get();

        foreach ($packagings as $pkg) {
            $deductedQty = (float) $pkg->quantity_deducted;
            $rawMat = $pkg->rawMaterial;

            $unitCost = 0.0;
            if ($rawMat) {
                $batchCost = \Illuminate\Support\Facades\DB::table('inventory_batches')
                    ->where('raw_material_id', $rawMat->id)
                    ->where(function ($q) {
                        $q->where('unit_cost', '>', 0)->orWhere('purchase_rate', '>', 0);
                    })
                    ->latest('id')
                    ->value(\Illuminate\Support\Facades\DB::raw('COALESCE(unit_cost, purchase_rate)'));

                if ($batchCost && (float) $batchCost > 0) {
                    $unitCost = (float) $batchCost;
                } elseif (!empty($rawMat->unit_cost) && (float) $rawMat->unit_cost > 0) {
                    $unitCost = (float) $rawMat->unit_cost;
                } elseif (!empty($rawMat->purchase_rate) && (float) $rawMat->purchase_rate > 0) {
                    $unitCost = (float) $rawMat->purchase_rate;
                } else {
                    $unitCost = 10.00;
                }
            } else {
                $unitCost = 10.00;
            }

            $totalPackaging += ($deductedQty * $unitCost);
        }

        $unitFabric     = round($totalFabric / $targetQty, 2);
        $unitSubsidiary = round($totalSubsidiary / $targetQty, 2);
        $unitLabor      = round($totalLabor / $targetQty, 2);
        $unitWastage    = round($totalWastage / $targetQty, 2);
        $unitPackaging  = round($totalPackaging / $targetQty, 2);
        $totalUnitCost  = round($unitFabric + $unitSubsidiary + $unitLabor + $unitWastage + $unitPackaging, 2);

        return [
            'fabricCost'      => '₹' . number_format($unitFabric, 2),
            'subsidiaryCost'  => '₹' . number_format($unitSubsidiary, 2),
            'laborCost'       => '₹' . number_format($unitLabor, 2),
            'packagingCost'   => '₹' . number_format($unitPackaging, 2),
            'wastageCost'     => '₹' . number_format($unitWastage, 2),
            'totalUnitCost'   => '₹' . number_format($totalUnitCost, 2),
            'is_dynamic'      => true,
            'raw_values'      => [
                'fabric'      => $unitFabric,
                'subsidiary'  => $unitSubsidiary,
                'labor'       => $unitLabor,
                'packaging'   => $unitPackaging,
                'wastage'     => $unitWastage,
                'total'       => $totalUnitCost,
            ],
        ];
    }
}
