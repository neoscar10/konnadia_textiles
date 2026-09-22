<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_batch_id',
        'batch_code',
        'batch_date',
        'supervisor_id',
        'factory_supervisor_id',
        'cutter_id',
        'manufacturing_product_id',
        'pattern_id',
        'planned_quantity',
        'priority',
        'status',
        'completed_at',
        'is_converted',
        'unconverted_quantity',
        'converted_quantity',
        'remarks',
    ];

    protected $casts = [
        'batch_date' => 'date',
        'completed_at' => 'datetime',
        'is_converted' => 'boolean',
        'planned_quantity' => 'integer',
        'unconverted_quantity' => 'integer',
        'converted_quantity' => 'integer',
    ];

    /**
     * Check if the production batch is completed and ready for finished goods conversion.
     */
    public function isReadyForConversion(): bool
    {
        $job = $this->job;
        if (!$job) {
            return false;
        }

        return $this->isFullyCompleted() && !$this->is_converted;
    }

    /**
     * Check if all jobs in the batch are completed and all wastages/discrepancies recorded.
     */
    public function isFullyCompleted(): bool
    {
        $jobs = $this->jobs;
        if ($jobs->isEmpty() && $this->job) {
            $jobs = collect([$this->job]);
        }

        if ($jobs->isEmpty()) {
            return $this->status === 'Completed';
        }

        foreach ($jobs as $job) {
            if ($job->status !== 'completed') {
                return false;
            }
            if ($job->has_unresolved_discrepancy) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract unique Design IDs used in this production batch and aggregate total manufactured product counts.
     */
    public function getDesignIdsWithProductCounts(): array
    {
        $jobs = $this->jobs;
        if ($jobs->isEmpty() && $this->job) {
            $jobs = collect([$this->job]);
        }

        $designMap = [];

        foreach ($jobs as $job) {
            $jobDesigns = collect();

            $consumptions = $job->materialConsumptions()->with(['inventoryBaleRoll.bale', 'inventoryBatch'])->get();
            foreach ($consumptions as $mc) {
                if (!empty($mc->inventoryBaleRoll?->design_number)) {
                    $jobDesigns->push(trim($mc->inventoryBaleRoll->design_number));
                } elseif (!empty($mc->inventoryBaleRoll?->bale?->design_number)) {
                    $jobDesigns->push(trim($mc->inventoryBaleRoll->bale->design_number));
                } elseif (!empty($mc->inventoryBatch?->design_number)) {
                    $jobDesigns->push(trim($mc->inventoryBatch->design_number));
                }
            }

            if ($jobDesigns->isEmpty()) {
                if (!empty($job->pattern?->name)) {
                    $jobDesigns->push(trim($job->pattern->name));
                } elseif (!empty($this->pattern?->name)) {
                    $jobDesigns->push(trim($this->pattern->name));
                } else {
                    $jobDesigns->push('DSG-BATCH-' . $this->id);
                }
            }

            $uniqueJobDesigns = $jobDesigns->map(fn($d) => trim($d))->filter()->unique(fn($d) => mb_strtolower($d))->values();
            $jobQty = $job->completed_quantity > 0 ? $job->completed_quantity : ($job->target_quantity > 0 ? $job->target_quantity : $this->planned_quantity);
            $mfgProduct = $job->manufacturingProduct;

            foreach ($uniqueJobDesigns as $dId) {
                $trimmedDesign = trim($dId);
                $lookupKey = mb_strtolower($trimmedDesign);

                if (!isset($designMap[$lookupKey])) {
                    $designMap[$lookupKey] = [
                        'design_id' => $trimmedDesign,
                        'total_produced_qty' => 0,
                        'products' => [],
                    ];
                } else {
                    if (ctype_lower($designMap[$lookupKey]['design_id']) && !ctype_lower($trimmedDesign)) {
                        $designMap[$lookupKey]['design_id'] = $trimmedDesign;
                    }
                }

                $designMap[$lookupKey]['total_produced_qty'] += $jobQty;
                $pId = $mfgProduct?->id ?? 0;
                $pName = $mfgProduct?->name ?? ('Product #' . $job->id);

                if (!isset($designMap[$lookupKey]['products'][$pId])) {
                    $designMap[$lookupKey]['products'][$pId] = [
                        'id' => $pId,
                        'name' => $pName,
                        'qty' => 0,
                    ];
                }
                $designMap[$lookupKey]['products'][$pId]['qty'] += $jobQty;
            }
        }

        if (empty($designMap)) {
            $dId = 'DSG-BATCH-' . $this->id;
            $qty = $this->total_finished_quantity > 0 ? $this->total_finished_quantity : $this->planned_quantity;
            $mfgProduct = $this->manufacturingProduct;
            $designMap[mb_strtolower($dId)] = [
                'design_id' => $dId,
                'total_produced_qty' => $qty,
                'products' => [
                    ($mfgProduct?->id ?? 0) => [
                        'id' => $mfgProduct?->id ?? 0,
                        'name' => $mfgProduct?->name ?? 'Manufacturing Product',
                        'qty' => $qty,
                    ],
                ],
            ];
        }

        return array_values($designMap);
    }


    public static function generateNextBatchCode(): string
    {
        $year = date('Y');
        $maxNum = static::where('batch_code', 'like', "PB-{$year}-%")
            ->get()
            ->map(function ($b) use ($year) {
                $raw = str_replace("PB-{$year}-", '', $b->batch_code);
                $code = explode('-', $raw)[0];
                return (int) $code;
            })
            ->max() ?: 0;

        $nextNum = max($maxNum + 1, (int) (static::max('id') ?? 0) + 1);
        $candidate = sprintf("PB-%s-%04d", $year, $nextNum);

        while (static::where('batch_code', $candidate)->exists()) {
            $nextNum++;
            $candidate = sprintf("PB-%s-%04d", $year, $nextNum);
        }

        return $candidate;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $batch->batch_code = static::generateNextBatchCode();
            }

            if (empty($batch->batch_date)) {
                $batch->batch_date = now()->format('Y-m-d');
            }
        });

        static::updating(function ($batch) {
            if ($batch->isDirty('status') && $batch->status === 'Completed') {
                $product = $batch->manufacturingProduct;
                if ($product) {
                    $finalTask = $product->getFinalTask();
                    if ($finalTask && $batch->job) {
                        $finalStageCompleted = $batch->job->stageExecutions()
                            ->where('task_id', $finalTask->id)
                            ->where('status', 'completed')
                            ->exists();
                        if (!$finalStageCompleted) {
                            throw new \Exception("Cannot set batch status to Completed before the designated Final Production Step [{$finalTask->name}] is completed.");
                        }
                    }
                }
            }
        });
    }

    /**
     * Get the parent batch (if this is a child alteration batch).
     */
    public function parentBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'parent_batch_id');
    }

    /**
     * Get child alteration batches generated from this batch.
     */
    public function childBatches()
    {
        return $this->hasMany(ProductionBatch::class, 'parent_batch_id');
    }

    /**
     * Get the supervisor (legacy: User record) assigned to this batch.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the factory supervisor assigned to this batch.
     */
    public function factorySupervisor()
    {
        return $this->belongsTo(FactorySupervisor::class, 'factory_supervisor_id');
    }

    /**
     * Get the designated primary cutter assigned to this batch.
     */
    public function cutter()
    {
        return $this->belongsTo(Labor::class, 'cutter_id');
    }

    /**
     * Get the manufacturing product associated with this batch.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get the pattern (variant/routing template) associated with this batch.
     */
    public function pattern()
    {
        return $this->belongsTo(ManufacturingProductPattern::class, 'pattern_id');
    }

    /**
     * Get the single master production job for this batch.
     */
    public function job()
    {
        return $this->hasOne(ProductionJob::class, 'production_batch_db_id');
    }

    /**
     * Get all jobs created for this batch.
     */
    public function jobs()
    {
        return $this->hasMany(ProductionJob::class, 'production_batch_db_id');
    }

    /**
     * Get all labor allocations across all jobs in this batch.
     */
    public function laborAllocations()
    {
        return $this->hasManyThrough(
            JobLaborAllocation::class,
            ProductionJob::class,
            'production_batch_db_id',
            'job_id',
            'id',
            'job_code'
        );
    }

    /**
     * Get all material consumptions across all jobs in this batch.
     */
    public function materialConsumptions()
    {
        return $this->hasManyThrough(
            JobMaterialConsumption::class,
            ProductionJob::class,
            'production_batch_db_id',
            'production_job_id',
            'id',
            'id'
        );
    }

    /**
     * Get all product outputs across all jobs in this batch.
     */
    public function productOutputs()
    {
        return $this->hasManyThrough(
            JobProductionOutput::class,
            ProductionJob::class,
            'production_batch_db_id',
            'production_job_id',
            'id',
            'id'
        );
    }

    /**
     * Get all wastage records across all jobs in this batch.
     */
    public function wastageRecords()
    {
        return $this->hasManyThrough(
            JobWastage::class,
            ProductionJob::class,
            'production_batch_db_id',
            'production_job_id',
            'id',
            'id'
        );
    }

    /**
     * Get all alteration records across all jobs in this batch.
     */
    public function alterationRecords()
    {
        return $this->hasManyThrough(
            JobAlteration::class,
            ProductionJob::class,
            'production_batch_db_id',
            'production_job_id',
            'id',
            'id'
        );
    }

    // --- Dynamic Consolidated Accessors for 360 Ledger ---

    public function getTotalLaborCostAttribute(): float
    {
        return (float) $this->laborAllocations()->sum('calculated_wage');
    }

    public function getTotalMaterialCostAttribute(): float
    {
        return (float) $this->materialConsumptions()->sum('total_cost');
    }

    public function getTotalProductionCostAttribute(): float
    {
        return $this->total_labor_cost + $this->total_material_cost;
    }

    public function getTotalWastageQuantityAttribute(): float
    {
        return (float) $this->wastageRecords()->sum('quantity_wasted');
    }

    public function getTotalAlterationQuantityAttribute(): int
    {
        return (int) $this->alterationRecords()->sum('source_quantity');
    }

    public function getTotalFinishedQuantityAttribute(): int
    {
        $total = 0;
        foreach ($this->jobs as $job) {
            $q = (int) $job->total_produced_quantity;
            if ($q <= 0) {
                $q = (int) $job->target_quantity;
            }
            $total += $q;
        }
        return $total > 0 ? $total : (int) ($this->planned_quantity ?? 0);
    }

    public function getRemainingUnconvertedQuantityAttribute(): int
    {
        $totalFinished = $this->total_finished_quantity > 0 ? $this->total_finished_quantity : $this->planned_quantity;
        return max(0, $totalFinished - (int) $this->converted_quantity);
    }
}
