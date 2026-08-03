<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'product_combination_id',
        'quantity_change',
        'unit_cost',
        'reference_type',
        'reference_id',
        'movement_type',
        'notes',
    ];

    protected $casts = [
        'quantity_change' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productCombination()
    {
        return $this->belongsTo(ProductCombination::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
