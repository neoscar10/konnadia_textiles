<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobAlteration extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_code',
        'production_job_id',
        'source_product_id',
        'source_quantity',
        'target_product_id',
        'target_quantity',
        'child_production_batch_id',
    ];

    protected $casts = [
        'source_quantity' => 'integer',
        'target_quantity' => 'integer',
    ];

    /**
     * Get the source manufacturing product.
     */
    public function sourceProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'source_product_id');
    }

    /**
     * Get the target manufacturing product.
     */
    public function targetProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'target_product_id');
    }

    /**
     * Get the generated child production batch.
     */
    public function childBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'child_production_batch_id');
    }

    /**
     * Get the parent production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }
}
