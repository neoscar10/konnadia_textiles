<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobWastage extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'manufacturing_product_id',
        'task_id',
        'quantity_wasted',
        'reason',
    ];

    protected $casts = [
        'quantity_wasted' => 'decimal:2',
    ];

    /**
     * Get the manufacturing product (if specific product damaged).
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class);
    }

    /**
     * Get the production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the task stage where wastage occurred.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
