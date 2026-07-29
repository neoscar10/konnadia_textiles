<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufacturingProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'standard_labor_rate',
        'manufacturing_product_category_id',
    ];

    protected $casts = [
        'standard_labor_rate' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $latestId = static::max('id') ?? 0;
                $product->code = 'MP-BED-' . str_pad($latestId + 1, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the manufacturing product category this product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ManufacturingProductCategory::class, 'manufacturing_product_category_id');
    }

    /**
     * Tasks associated with this manufacturing product, ordered by routing sequence.
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'manufacturing_product_task')
                    ->withPivot('standard_labor_rate', 'sequence_number', 'is_final_step')
                    ->orderByPivot('sequence_number', 'asc')
                    ->withTimestamps();
    }

    /**
     * Return the final step task for this product's routing.
     */
    public function getFinalTask(): ?Task
    {
        return $this->tasks()->wherePivot('is_final_step', true)->first();
    }

    /**
     * Allocations associated with this manufacturing product.
     */
    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class);
    }

    /**
     * Retrieve the Standard Labor Rate for a given Task.
     * Looks up the pivot table rate first, falling back to the product's base standard_labor_rate.
     *
     * @param int $taskId
     * @return float|null
     */
    public function getStandardLaborRateForTask($taskId): ?float
    {
        $task = $this->tasks()->where('task_id', $taskId)->first();

        if ($task && !is_null($task->pivot->standard_labor_rate)) {
            return (float) $task->pivot->standard_labor_rate;
        }

        return !is_null($this->standard_labor_rate) ? (float) $this->standard_labor_rate : null;
    }

    /**
     * Retrieve the next task in the routing sequence for this manufacturing product,
     * or return null if current task is the final step.
     *
     * @param int $currentTaskId
     * @return Task|null
     */
    public function getNextTask($currentTaskId): ?Task
    {
        $orderedTasks = $this->tasks;
        if ($orderedTasks->isEmpty()) {
            $orderedTasks = Task::where('status', true)->get();
        }

        $currentIndex = null;
        foreach ($orderedTasks as $idx => $task) {
            if ($task->id == $currentTaskId) {
                $currentIndex = $idx;
                break;
            }
        }

        if ($currentIndex !== null && isset($orderedTasks[$currentIndex + 1])) {
            return $orderedTasks[$currentIndex + 1];
        }

        return null;
    }
}
