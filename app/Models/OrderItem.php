<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_combination_id',
        'product_unit_id',
        'product_title',
        'product_sku',
        'hsn_code',
        'selected_options',
        'unit_name',
        'unit_short_code',
        'unit_conversion_quantity',
        'quantity',
        'quantity_lvl1',
        'quantity_lvl2',
        'base_unit_price',
        'customer_unit_price',
        'line_subtotal',
        'gst_percentage',
        'gst_amount',
        'line_total',
        'status',
        'dispatch_note',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'quantity' => 'integer',
        'quantity_lvl1' => 'integer',
        'quantity_lvl2' => 'integer',
        'unit_conversion_quantity' => 'decimal:4',
        'base_unit_price' => 'decimal:2',
        'customer_unit_price' => 'decimal:2',
        'line_subtotal' => 'decimal:2',
        'gst_percentage' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
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
