<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontEndProductPackaging extends Model
{
    use HasFactory;

    protected $fillable = [
        'front_end_product_id',
        'raw_material_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function frontEndProduct()
    {
        return $this->belongsTo(FrontEndProduct::class, 'front_end_product_id');
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
