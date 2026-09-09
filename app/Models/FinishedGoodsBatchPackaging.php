<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinishedGoodsBatchPackaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'finished_goods_batch_id',
        'raw_material_id',
        'quantity_deducted',
    ];

    protected $casts = [
        'quantity_deducted' => 'integer',
    ];

    public function finishedGoodsBatch()
    {
        return $this->belongsTo(FinishedGoodsBatch::class, 'finished_goods_batch_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
