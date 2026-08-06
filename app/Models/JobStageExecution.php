<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobStageExecution extends Model
{
    use HasFactory;

    protected $table = 'job_stage_executions';

    protected $fillable = [
        'production_job_id',
        'task_id',
        'sequence_number',
        'target_quantity',
        'status',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'target_quantity' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the production job this stage execution belongs to.
     */
    public function job()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the task routing stage for this execution.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get all labor allocations logged under this job and task.
     */
    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class, 'task_id', 'task_id')
            ->whereHas('productionJob', function ($q) {
                $q->where('id', $this->production_job_id);
            });
    }

    /**
     * Get all product outputs logged under this job and task.
     */
    public function productOutputs()
    {
        return $this->hasMany(JobProductionOutput::class, 'task_id', 'task_id')
            ->where('production_job_id', $this->production_job_id);
    }

    /**
     * Get all material consumptions logged under this job and task.
     */
    public function materialConsumptions()
    {
        return $this->hasMany(JobMaterialConsumption::class, 'task_id', 'task_id')
            ->where('production_job_id', $this->production_job_id);
    }

    /**
     * Get all wastages logged under this job and task.
     */
    public function wastages()
    {
        return $this->hasMany(JobWastage::class, 'task_id', 'task_id')
            ->where('production_job_id', $this->production_job_id);
    }

    /**
     * Get total completed quantity processed for this stage.
     */
    public function getCompletedQuantityAttribute(): int
    {
        $explicitOutputs = (int) $this->productOutputs()->sum('quantity_produced');
        if ($explicitOutputs > 0) {
            return $explicitOutputs;
        }

        return (int) JobLaborAllocation::whereHas('productionJob', function ($q) {
                $q->where('id', $this->production_job_id);
            })
            ->where('task_id', $this->task_id)
            ->sum('quantity_processed');
    }

    /**
     * Get remaining/pending quantity available to process in this stage.
     */
    public function getPendingQuantityAttribute(): int
    {
        return max(0, $this->target_quantity - $this->completed_quantity);
    }
}
