<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCustomerLevelPrice extends Model
{
    protected $table = 'product_customer_level_prices';

    protected $fillable = [
        'product_id',
        'customer_level_id',
        'discount_percentage',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customerLevel()
    {
        return $this->belongsTo(CustomerLevel::class);
    }
}
