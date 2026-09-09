<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinishedGoodsBatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'barcode',
        'front_end_product_id',
        'design_id',
        'converted_qty',
        'unit',
        'unit_factor',
        'converted_date',
        'is_published',
        'created_by',
        'costing_summary',
        'notes',
    ];

    protected $casts = [
        'converted_qty' => 'integer',
        'unit_factor' => 'integer',
        'converted_date' => 'datetime',
        'is_published' => 'boolean',
        'costing_summary' => 'array',
    ];

    public function frontEndProduct()
    {
        return $this->belongsTo(FrontEndProduct::class, 'front_end_product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(FinishedGoodsBatchItem::class, 'finished_goods_batch_id');
    }

    public function packagingDeductions()
    {
        return $this->hasMany(FinishedGoodsBatchPackaging::class, 'finished_goods_batch_id');
    }
}
