<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUnit extends Model
{
    protected $table = 'product_units';

    protected $fillable = [
        'product_id',
        'level',
        'name',
        'short_code',
        'conversion_to_base',
    ];

    protected $casts = [
        'level' => 'integer',
        'conversion_to_base' => 'decimal:4',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
