<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'product_combination_id',
        'product_unit_id',
        'quantity',
        'unit_conversion_quantity',
        'base_unit_price',
        'customer_unit_price',
        'line_subtotal',
        'gst_percentage',
        'gst_amount',
        'line_total',
        'selected_options',
        'hsn_code',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'quantity' => 'integer',
        'unit_conversion_quantity' => 'decimal:4',
        'base_unit_price' => 'decimal:2',
        'customer_unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function combination()
    {
        return $this->belongsTo(ProductCombination::class, 'product_combination_id');
    }

    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
