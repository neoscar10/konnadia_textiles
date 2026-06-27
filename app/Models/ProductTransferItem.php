<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransferItem extends Model
{
    use HasFactory;

    protected $table = 'product_transfer_items';

    protected $fillable = [
        'product_transfer_id',
        'product_id',
        'product_combination_id',
        'product_unit_id',
        'product_title',
        'product_sku',
        'selected_options',
        'unit_name',
        'unit_short_code',
        'unit_conversion_quantity',
        'quantity',
        'base_quantity',
        'available_stock_before',
        'available_stock_after',
        'stock_tracked',
        'stock_deducted',
        'note',
    ];

    protected $casts = [
        'selected_options' => 'array',
        'unit_conversion_quantity' => 'decimal:4',
        'quantity' => 'integer',
        'base_quantity' => 'decimal:4',
        'available_stock_before' => 'decimal:4',
        'available_stock_after' => 'decimal:4',
        'stock_tracked' => 'boolean',
        'stock_deducted' => 'boolean',
    ];

    /**
     * Transfer relationship.
     */
    public function transfer()
    {
        return $this->belongsTo(ProductTransfer::class, 'product_transfer_id');
    }

    /**
     * Product relationship.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    /**
     * Combination relationship.
     */
    public function combination()
    {
        return $this->belongsTo(ProductCombination::class, 'product_combination_id');
    }

    /**
     * Unit relationship.
     */
    public function unit()
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
