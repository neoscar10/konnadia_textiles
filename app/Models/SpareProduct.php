<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpareProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_batch_id',
        'production_job_id',
        'manufacturing_product_id',
        'design_id',
        'quantity',
        'used_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'used_quantity' => 'integer',
        'production_batch_id' => 'integer',
        'production_job_id' => 'integer',
        'manufacturing_product_id' => 'integer',
    ];

    /**
     * Available unconverted spare quantity.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->used_quantity);
    }

    /**
     * Get the source production batch.
     */
    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    /**
     * Get the source production job.
     */
    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }

    /**
     * Get the constituent manufacturing product.
     */
    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }
}
