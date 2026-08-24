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
        'manufacturing_product_id',
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

        return $this->status === 'Completed' 
            && !$this->is_converted 
            && $job->stageExecutions()->count() > 0 
            && !$job->stageExecutions()->where('status', '!=', 'completed')->exists();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_code)) {
                $year = date('Y');
                $latestId = static::max('id') ?? 0;
                $batch->batch_code = "PB-{$year}-" . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
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
     * Get the supervisor assigned to this batch.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the manufacturing product associated with this batch.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
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
