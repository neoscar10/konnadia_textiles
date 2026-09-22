<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_batch_id',
        'production_batch_db_id',
        'manufacturing_product_id',
        'pattern_id',
        'supervisor_id',
        'factory_supervisor_id',
        'job_date',
        'target_quantity',
        'converted_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'job_date' => 'date',
        'target_quantity' => 'integer',
        'converted_quantity' => 'integer',
    ];

    public static function generateNextJobCode(): string
    {
        $year = date('Y');
        $maxNum = static::where('job_code', 'like', "JOB-{$year}-%")
            ->get()
            ->map(function ($j) use ($year) {
                $raw = str_replace("JOB-{$year}-", '', $j->job_code);
                $code = explode('-', $raw)[0];
                return (int) $code;
            })
            ->max() ?: 0;

        $nextNum = max($maxNum + 1, (int) (static::max('id') ?? 0) + 1);
        $candidate = sprintf("JOB-%s-%04d", $year, $nextNum);

        while (static::where('job_code', $candidate)->exists()) {
            $nextNum++;
            $candidate = sprintf("JOB-%s-%04d", $year, $nextNum);
        }

        return $candidate;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->job_code)) {
                $job->job_code = static::generateNextJobCode();
            }

            if (empty($job->job_date)) {
                $job->job_date = now()->format('Y-m-d');
            }
        });
    }

    /**
     * Get the parent batch record.
     */
    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_db_id');
    }

    public function getBatchAttribute()
    {
        if ($this->relationLoaded('batch') && $this->getRelation('batch')) {
            return $this->getRelation('batch');
        }
        $rel = $this->batch()->getResults();
        if ($rel) return $rel;

        if (!empty($this->production_batch_id)) {
            return ProductionBatch::where('batch_code', $this->production_batch_id)->first();
        }
        return null;
    }

    /**
     * Get the manufacturing product.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }

    /**
     * Get the pattern (variant/routing template) associated with this job.
     */
    public function pattern()
    {
        return $this->belongsTo(ManufacturingProductPattern::class, 'pattern_id');
    }

    /**
     * Get all stage executions for this single master job.
     */
    public function stageExecutions()
    {
        return $this->hasMany(JobStageExecution::class, 'production_job_id')->orderBy('sequence_number');
    }

    /**
     * Ensure all product routing tasks exist as stage execution records.
     */
    public function ensureStageExecutionsExist(): void
    {
        $product = $this->manufacturingProduct;
        $routingTasks = $product ? $product->tasks : Task::where('status', true)->get();
        if ($routingTasks->isEmpty()) {
            return;
        }

        $existingExecutions = $this->stageExecutions;
        $existingTaskIds = $existingExecutions->pluck('task_id')->toArray();

        foreach ($routingTasks as $idx => $task) {
            if (!in_array($task->id, $existingTaskIds)) {
                $seq = $task->pivot->sequence_number ?? ($idx + 1);
                
                $prevExec = $existingExecutions->where('sequence_number', '<', $seq)->sortByDesc('sequence_number')->first();
                $targetQty = $prevExec ? ($prevExec->target_quantity > 0 ? $prevExec->target_quantity : $this->target_quantity) : $this->target_quantity;

                JobStageExecution::create([
                    'production_job_id' => $this->id,
                    'task_id' => $task->id,
                    'sequence_number' => $seq,
                    'target_quantity' => $targetQty,
                    'status' => 'pending',
                ]);
            }
        }
    }

    /**
     * Get the supervisor assigned to this job (legacy User record).
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the factory supervisor assigned to this job.
     */
    public function factorySupervisor()
    {
        return $this->belongsTo(FactorySupervisor::class, 'factory_supervisor_id');
    }

    /**
     * Resolve effective supervisor model (FactorySupervisor preferred over User).
     */
    public function getEffectiveSupervisorAttribute()
    {
        if ($this->factorySupervisor) {
            return $this->factorySupervisor;
        }
        if ($this->factory_supervisor_id) {
            $fs = FactorySupervisor::find($this->factory_supervisor_id);
            if ($fs) return $fs;
        }
        if ($this->batch && $this->batch->factorySupervisor) {
            return $this->batch->factorySupervisor;
        }
        if ($this->supervisor_id) {
            $fs = FactorySupervisor::find($this->supervisor_id);
            if ($fs) return $fs;
        }
        return $this->supervisor;
    }

    /**
     * Get the task for this job.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get all labor allocations for this job.
     */
    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class, 'job_id', 'job_code');
    }

    public function laborAllocations()
    {
        return $this->allocations();
    }

    /**
     * Get all material consumptions for this job.
     */
    public function materialConsumptions()
    {
        return $this->hasMany(JobMaterialConsumption::class, 'production_job_id');
    }

    /**
     * Get all product outputs for this job.
     */
    public function productOutputs()
    {
        return $this->hasMany(JobProductionOutput::class, 'production_job_id');
    }

    public function outputs()
    {
        return $this->productOutputs();
    }

    /**
     * Get all wastage records for this job.
     */
    public function wastages()
    {
        return $this->hasMany(JobWastage::class, 'production_job_id');
    }

    /**
     * Get all alteration records for this job.
     */
    public function alterations()
    {
        return $this->hasMany(JobAlteration::class, 'production_job_id');
    }

    /**
     * Get completed quantity based on the average output across product routing stages
     * or final stage output, capped at target quantity.
     */
    public function getCompletedQuantityAttribute(): int
    {
        if ($this->target_quantity <= 0) {
            return 0;
        }

        $stageExecs = $this->stageExecutions()->get();
        if ($stageExecs->count() > 0) {
            $finalExec = $stageExecs->sortByDesc('sequence_number')->first();
            if ($finalExec) {
                $finalOutput = (int) $this->productOutputs()->where('task_id', $finalExec->task_id)->sum('quantity_produced');
                if ($finalOutput > 0) return (int) min($this->target_quantity, $finalOutput);
                if ($finalExec->status === 'completed' && $finalExec->completed_quantity > 0) {
                    return (int) min($this->target_quantity, $finalExec->completed_quantity);
                }
            }

            $lastCompleted = $stageExecs->where('status', 'completed')->sortByDesc('sequence_number')->first();
            if ($lastCompleted) {
                $lastOutput = (int) $this->productOutputs()->where('task_id', $lastCompleted->task_id)->sum('quantity_produced');
                if ($lastOutput > 0) return (int) min($this->target_quantity, $lastOutput);
                if ($lastCompleted->completed_quantity > 0) return (int) min($this->target_quantity, $lastCompleted->completed_quantity);
            }
        }

        $rawStatus = $this->attributes['status'] ?? 'pending';
        if ($rawStatus === 'completed') {
            return (int) $this->target_quantity;
        }

        $sum = (int) max($this->productOutputs()->sum('quantity_produced'), $this->allocations()->sum('quantity_processed'));
        return (int) min($this->target_quantity, $sum);
    }

    /**
     * Dynamically resolve job status, automatically returning 'completed' if overall output progress hits 100%.
     */
    public function getStatusAttribute($value): string
    {
        $rawStatus = $value ?? ($this->attributes['status'] ?? 'pending');
        if (!$this->exists || $rawStatus === 'completed' || $rawStatus === 'cancelled') {
            return $rawStatus;
        }

        $targetQty = (int) $this->target_quantity;
        if ($targetQty > 0) {
            $stageExecs = $this->stageExecutions()->get();
            $isAllStagesCompleted = $stageExecs->count() > 0 && $stageExecs->where('status', '!=', 'completed')->count() === 0;
            $isTargetQuantityMet = $this->getCompletedQuantityAttribute() >= $targetQty;

            if ($isAllStagesCompleted || $isTargetQuantityMet) {
                if ($rawStatus !== 'completed') {
                    \Illuminate\Support\Facades\DB::table('production_jobs')->where('id', $this->id)->update(['status' => 'completed']);
                    $this->attributes['status'] = 'completed';
                }
                return 'completed';
            }
        }

        return $rawStatus;
    }

    /**
     * Calculate overall completion progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_quantity <= 0) {
            return 0.0;
        }

        if ($this->status === 'completed') {
            return 100.0;
        }

        $completed = $this->completed_quantity;
        return (float) min(100, round(($completed / $this->target_quantity) * 100, 1));
    }

    /**
     * Get sequence number of this job's task in the manufacturing product's routing.
     */
    public function getSequenceNumberAttribute(): int
    {
        if (!$this->manufacturingProduct || !$this->task_id) {
            return 1;
        }

        $task = $this->manufacturingProduct->tasks->firstWhere('id', $this->task_id);
        return $task?->pivot?->sequence_number ?? 1;
    }

    /**
     * Check if this job's task is designated as the final step in the product's routing.
     */
    public function getIsFinalStepAttribute(): bool
    {
        if (!$this->manufacturingProduct || !$this->task_id) {
            return false;
        }

        $task = $this->manufacturingProduct->tasks->firstWhere('id', $this->task_id);
        return (bool) ($task?->pivot?->is_final_step ?? false);
    }

    /**
     * Get virtual input_quantity mapping to target_quantity.
     */
    public function getInputQuantityAttribute()
    {
        return $this->target_quantity;
    }

    /**
     * Set virtual input_quantity mapping to target_quantity.
     */
    public function setInputQuantityAttribute($value)
    {
        $this->target_quantity = $value;
    }

    /**
     * Get final produced yield quantity (from final stage or last active/completed stage).
     * Evaluates output of the final stage rather than summing outputs across intermediate steps.
     */
    public function getFinalProducedYieldAttribute(): int
    {
        $stageExecs = $this->stageExecutions()->get();
        if ($stageExecs->isNotEmpty()) {
            // Check output of final stage execution (highest sequence number)
            $finalExec = $stageExecs->sortByDesc('sequence_number')->first();
            if ($finalExec) {
                $finalOutput = (int) $this->productOutputs()->where('task_id', $finalExec->task_id)->sum('quantity_produced');
                if ($finalOutput > 0) {
                    return $finalOutput;
                }
                if ($finalExec->status === 'completed' && $finalExec->completed_quantity > 0) {
                    return (int) $finalExec->completed_quantity;
                }
            }

            // Check output of the most recent completed stage
            $lastCompleted = $stageExecs->where('status', 'completed')->sortByDesc('sequence_number')->first();
            if ($lastCompleted) {
                $lastOutput = (int) $this->productOutputs()->where('task_id', $lastCompleted->task_id)->sum('quantity_produced');
                if ($lastOutput > 0) {
                    return $lastOutput;
                }
                if ($lastCompleted->completed_quantity > 0) {
                    return (int) $lastCompleted->completed_quantity;
                }
            }

            // Check output of current in-progress stage
            $inProgress = $stageExecs->where('status', 'in_progress')->sortBy('sequence_number')->first();
            if ($inProgress) {
                $inProgOutput = (int) $this->productOutputs()->where('task_id', $inProgress->task_id)->sum('quantity_produced');
                if ($inProgOutput > 0) {
                    return $inProgOutput;
                }
            }
        }

        // Fallback for product task routing
        $product = $this->manufacturingProduct;
        if ($product && $product->tasks()->count() > 0) {
            $finalTask = $product->tasks->firstWhere('pivot.is_final_step', true) ?? $product->tasks->last();
            if ($finalTask) {
                $finalOutput = (int) $this->productOutputs()->where('task_id', $finalTask->id)->sum('quantity_produced');
                if ($finalOutput > 0) {
                    return $finalOutput;
                }
            }
        }

        // Fallback for completed job or single task
        if (($this->attributes['status'] ?? '') === 'completed') {
            $lastOutput = (int) $this->productOutputs()->latest('id')->value('quantity_produced');
            if ($lastOutput > 0) return $lastOutput;

            $lastStageExec = $stageExecs->sortByDesc('sequence_number')->first();
            if ($lastStageExec && $lastStageExec->completed_quantity > 0) {
                return (int) $lastStageExec->completed_quantity;
            }

            return (int) $this->target_quantity;
        }

        return (int) min($this->target_quantity, $this->completed_quantity);
    }

    /**
     * Get total produced quantity available for conversion.
     */
    public function getTotalProducedQuantityAttribute(): int
    {
        if ($this->status !== 'completed') {
            return 0;
        }
        return $this->final_produced_yield;
    }

    /**
     * Get remaining unconverted quantity available for storefront conversion.
     */
    public function getRemainingUnconvertedQuantityAttribute(): int
    {
        if ($this->status !== 'completed') {
            return 0;
        }
        $produced = $this->total_produced_quantity;
        $converted = (int) ($this->converted_quantity ?? 0);
        return max(0, $produced - $converted);
    }

    /**
     * Get storefront conversion status string.
     */
    public function getConversionStatusAttribute(): string
    {
        if ($this->status !== 'completed') {
            return 'not_completed';
        }

        $converted = (int) ($this->converted_quantity ?? 0);
        $remaining = $this->remaining_unconverted_quantity;

        if ($converted > 0 && $remaining === 0) {
            return 'fully_converted';
        }

        if ($converted > 0 && $remaining > 0) {
            return 'partially_converted';
        }

        return 'unconverted';
    }

    /**
     * Get configured subsidiary materials for this job (checking pattern BOM first, falling back to product BOM).
     */
    public function getEffectiveSubsidiaryMaterials()
    {
        if ($this->pattern && $this->pattern->subsidiaryMaterials()->exists()) {
            return $this->pattern->subsidiaryMaterials;
        }

        if ($this->manufacturingProduct && $this->manufacturingProduct->subsidiaryMaterials()->exists()) {
            return $this->manufacturingProduct->subsidiaryMaterials;
        }

        return collect();
    }

    /**
     * Get initial cut quantity (from cutting stage output / stage 1).
     */
    public function getInitialCutQuantityAttribute(): int
    {
        $stageExecs = $this->stageExecutions()->get();
        if ($stageExecs->isNotEmpty()) {
            $firstStage = $stageExecs->sortBy('sequence_number')->first();
            if ($firstStage) {
                $outputQty = (int) $this->productOutputs()->where('task_id', $firstStage->task_id)->sum('quantity_produced');
                if ($outputQty > 0) return $outputQty;
                if ($firstStage->completed_quantity > 0) return (int) $firstStage->completed_quantity;
            }
        }
        return (int) $this->target_quantity;
    }

    /**
     * Get discrepancy between initial cut quantity and final completed output quantity.
     */
    public function getDiscrepancyQuantityAttribute(): int
    {
        if ($this->status !== 'completed') {
            return 0;
        }
        $cutQty = $this->initial_cut_quantity;
        $finalYield = $this->final_produced_yield;
        return max(0, $cutQty - $finalYield);
    }

    /**
     * Get total recorded discrepancy items (scrap + damage + alterations).
     */
    public function getRecordedDiscrepancyQuantityAttribute(): int
    {
        $scrap = (int) $this->wastages()->sum('quantity_wasted');
        $alt = (int) $this->alterations()->sum('source_quantity');
        return $scrap + $alt;
    }

    /**
     * Check if job has an unresolved discrepancy.
     */
    public function getHasUnresolvedDiscrepancyAttribute(): bool
    {
        if ($this->status !== 'completed') {
            return false;
        }
        $discrepancy = $this->discrepancy_quantity;
        if ($discrepancy <= 0) {
            return false;
        }
        $recorded = $this->recorded_discrepancy_quantity;
        return $recorded < $discrepancy;
    }
}
