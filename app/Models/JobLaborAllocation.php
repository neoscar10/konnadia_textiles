<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLaborAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_batch_id',
        'job_id',
        'labor_id',
        'manufacturing_product_id',
        'task_id',
        'quantity_processed',
        'calculated_wage',
    ];

    protected $casts = [
        'quantity_processed' => 'integer',
        'calculated_wage' => 'decimal:2',
    ];

    /**
     * Get the labor assigned to this allocation.
     */
    public function labor()
    {
        return $this->belongsTo(Labor::class);
    }

    /**
     * Get the task performed in this allocation.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the manufacturing product associated with this allocation.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get the parent production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'job_id', 'job_code');
    }
}
