<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobProductionOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'manufacturing_product_id',
        'task_id',
        'quantity_produced',
    ];

    protected $casts = [
        'quantity_produced' => 'integer',
    ];

    /**
     * Get the manufacturing product outputted.
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
     * Get the task stage for this output entry.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
