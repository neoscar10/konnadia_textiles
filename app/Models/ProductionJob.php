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
        'supervisor_id',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->job_code)) {
                $year = date('Y');
                $latestId = static::max('id') ?? 0;
                $job->job_code = "JOB-{$year}-" . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
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
     * Get the supervisor assigned to this job.
     */
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the manufacturing product for this job.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get all labor allocations for this job.
     */
    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class, 'job_id', 'job_code');
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

        $rawStatus = $this->attributes['status'] ?? 'pending';
        if ($rawStatus === 'completed') {
            return (int) $this->target_quantity;
        }

        // Check stage executions if present
        $stageExecs = $this->stageExecutions()->get();
        if ($stageExecs->count() > 0) {
            $allCompleted = $stageExecs->where('status', '!=', 'completed')->count() === 0;
            if ($allCompleted) {
                return (int) $this->target_quantity;
            }

            $stageCompletions = [];
            foreach ($stageExecs as $exec) {
                $outputQty = (int) $this->productOutputs()->where('task_id', $exec->task_id)->sum('quantity_produced');
                $laborQty = (int) $this->allocations()->where('task_id', $exec->task_id)->sum('quantity_processed');
                $done = max($exec->completed_quantity, $outputQty, $laborQty);
                if ($exec->status === 'completed') {
                    $done = max($done, $exec->target_quantity > 0 ? $exec->target_quantity : $this->target_quantity);
                }
                $stageCompletions[] = min($this->target_quantity, $done);
            }
            $avg = array_sum($stageCompletions) / count($stageCompletions);
            return (int) min($this->target_quantity, round($avg));
        }

        $product = $this->manufacturingProduct;
        if ($product && $product->tasks()->count() > 0) {
            $taskIds = $product->tasks()->pluck('tasks.id');
            $stageSums = [];
            foreach ($taskIds as $tid) {
                $outputQty = (int) $this->productOutputs()->where('task_id', $tid)->sum('quantity_produced');
                $laborQty = (int) $this->allocations()->where('task_id', $tid)->sum('quantity_processed');
                $stageSums[] = max($outputQty, $laborQty);
            }
            $avgCompleted = array_sum($stageSums) / count($stageSums);
            return (int) min($this->target_quantity, round($avgCompleted));
        }

        // Fallback for single task or unlinked product
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
     * Get total produced quantity available for conversion.
     */
    public function getTotalProducedQuantityAttribute(): int
    {
        return $this->completed_quantity;
    }

    /**
     * Get remaining unconverted quantity available for storefront conversion.
     */
    public function getRemainingUnconvertedQuantityAttribute(): int
    {
        $produced = $this->total_produced_quantity;
        $converted = (int) ($this->converted_quantity ?? 0);
        return max(0, $produced - $converted);
    }

    /**
     * Get storefront conversion status string.
     */
    public function getConversionStatusAttribute(): string
    {
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
}
