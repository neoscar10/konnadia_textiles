<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobMaterialConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'inventory_batch_id',
        'task_id',
        'quantity_consumed',
        'unit_cost',
        'total_cost',
        'consumed_length',
        'calculated_base_cost',
        'allocated_wastage_cost',
        'total_fabric_cost',
    ];

    protected $casts = [
        'quantity_consumed' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'consumed_length' => 'decimal:4',
        'calculated_base_cost' => 'decimal:2',
        'allocated_wastage_cost' => 'decimal:2',
        'total_fabric_cost' => 'decimal:2',
    ];

    /**
     * Get the inventory batch consumed.
     */
    public function inventoryBatch()
    {
        return $this->belongsTo(InventoryBatch::class);
    }

    /**
     * Get the production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the task stage for this consumption.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
