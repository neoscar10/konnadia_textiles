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
        'task_id',
        'supervisor_id',
        'job_date',
        'target_quantity',
        'status',
        'notes',
    ];

    protected $casts = [
        'job_date' => 'date',
        'target_quantity' => 'integer',
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
     * Get the task stage for this job.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
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

        $product = $this->manufacturingProduct;
        if ($product && $product->tasks()->count() > 0) {
            $taskIds = $product->tasks()->pluck('tasks.id');
            $stageSums = [];
            foreach ($taskIds as $tid) {
                $stageSums[] = (int) $this->allocations()->where('task_id', $tid)->sum('quantity_processed');
            }
            $avgCompleted = array_sum($stageSums) / count($stageSums);
            return (int) min($this->target_quantity, round($avgCompleted));
        }

        // Fallback for single task or unlinked product
        $sum = (int) $this->allocations()->sum('quantity_processed');
        return (int) min($this->target_quantity, $sum);
    }

    /**
     * Calculate overall completion progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_quantity <= 0) {
            return 0;
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
}
