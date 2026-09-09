<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FrontEndProductComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'front_end_product_id',
        'manufacturing_product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function frontEndProduct()
    {
        return $this->belongsTo(FrontEndProduct::class, 'front_end_product_id');
    }

    public function manufacturingProduct()
    {
        return $this->belongsTo(ManufacturingProduct::class, 'manufacturing_product_id');
    }
}
