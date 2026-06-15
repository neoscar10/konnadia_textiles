<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCombination extends Model
{
    protected $table = 'product_combinations';

    protected $fillable = [
        'product_id',
        'sku',
        'combination_values',
        'price',
        'stock_quantity',
        'is_active',
    ];

    protected $casts = [
        'combination_values' => 'array',
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
