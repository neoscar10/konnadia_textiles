<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishedGoodsBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'finished_goods_batch_id',
        'manufacturing_product_id',
        'production_batch_id',
        'production_job_id',
        'quantity_used',
    ];

    protected $casts = [
        'quantity_used' => 'integer',
    ];

    public function finishedGoodsBatch()
    {
        return $this->belongsTo(FinishedGoodsBatch::class, 'finished_goods_batch_id');
    }

    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function productionJob()
    {
        return $this->belongsTo(ProductionJob::class, 'production_job_id');
    }
}
