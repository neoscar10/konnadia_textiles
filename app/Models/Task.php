<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['name', 'code', 'status', 'consumes_raw_material', 'is_labor_required', 'sequence_number'];

    protected $casts = [
        'status' => 'boolean',
        'consumes_raw_material' => 'boolean',
        'is_labor_required' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->code)) {
                $latestId = static::max('id') ?? 0;
                $task->code = 'TSK-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function labors()
    {
        return $this->belongsToMany(Labor::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw('sequence_number IS NULL, sequence_number ASC')->orderBy('name', 'asc');
    }

    public function scopeLaborRequired($query)
    {
        return $query->where('is_labor_required', true);
    }

    public function requiresLabor(): bool
    {
        return (bool) $this->is_labor_required;
    }

    public function manufacturingProducts()
    {
        return $this->belongsToMany(ManufacturingProduct::class, 'manufacturing_product_task')
                    ->withPivot('standard_labor_rate')
                    ->withTimestamps();
    }

    public function allocations()
    {
        return $this->hasMany(JobLaborAllocation::class);
    }

    public function rawMaterialCategories()
    {
        return $this->belongsToMany(RawMaterialCategory::class, 'task_raw_material_category');
    }

    public function authorizedLaborTasks()
    {
        return $this->belongsToMany(Task::class, 'task_authorized_labor_task', 'task_id', 'authorized_task_id');
    }

    public function getEligibleLabors(): \Illuminate\Support\Collection
    {
        $authTaskIds = $this->authorizedLaborTasks()->pluck('tasks.id')->toArray();

        if (empty($authTaskIds)) {
            $authTaskIds = [$this->id];
        }

        $labors = Labor::active()
            ->whereHas('tasks', function ($q) use ($authTaskIds) {
                $q->whereIn('tasks.id', $authTaskIds);
            })
            ->orderBy('name')
            ->get();

        if ($labors->isEmpty()) {
            return Labor::active()->orderBy('name')->get();
        }

        return $labors;
    }
}
